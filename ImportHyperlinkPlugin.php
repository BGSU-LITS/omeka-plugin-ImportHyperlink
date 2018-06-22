<?php
/**
 * Omeka Import Hyperlink Plugin
 *
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2018 Bowling Green State University Libraries
 * @license MIT
 */

/**
 * Omeka Import Hyperlink Plugin: Plugin Class
 *
 * @package Import Hyperlink
 */
class ImportHyperlinkPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Plugin hooks.
     */
    protected $_hooks = array(
        'install',
        'uninstall',
        'upgrade',
        'config',
        'config_form',
        'after_save_item'
    );

    /**
     * @var array Plugin filters.
     */
    protected $_filters = array(
        'exhibit_attachment_markup',
        'addImportCheckboxToUrlElement' => array(
            'ElementInput', 'Item', ElementSet::ITEM_TYPE_NAME, 'URL'
        )
    );

    /**
     * @var array Plugin options.
     */
    protected $_options = array(
        'import_hyperlink_min_image_width' => 0,
        'import_hyperlink_min_image_height' => 0,
        'import_hyperlink_choose_bigger_image' => false,
        'import_hyperlink_embedly_key' => '',
        'import_hyperlink_nbclearn_token' => ''
    );

    /**
     * Plugin constructor.
     *
     * Requires class autoloader, and calls parent constructor.
     */
    public function __construct()
    {
        require 'vendor/autoload.php';
        parent::__construct();
    }

    /**
     * Hook to plugin installation.
     *
     * Installs the options for the plugin.
     */
    public function hookInstall()
    {
        // Install options.
        $this->_installOptions();
    }

    /**
     * Hook to plugin uninstallation.
     *
     * Uninstalls the options for the plugin.
     */
    public function hookUninstall()
    {
        $this->_uninstallOptions();
    }

    /**
     * Hook to plugin configuration form submission.
     *
     * Sets options submitted by the configuration form.
     */
    public function hookConfig($args)
    {
        foreach (array_keys($this->_options) as $option) {
            if (isset($args['post'][$option])) {
                set_option($option, $args['post'][$option]);
            }
        }
    }

    /**
     * Hook to plugin upgrade.
     *
     * Upgrades the options for the plugin.
     */
    public function hookUpgrade($args)
    {
        if (version_compare($args['old_version'], '2.0', '<')) {
            $options = array(
                'import_hyperlink_min_image_width' =>
                    'import_hyperlink_minImageWidth',
                'import_hyperlink_min_image_height' =>
                    'import_hyperlink_minImageHeight',
                'import_hyperlink_choose_bigger_image' =>
                    'import_hyperlink_getBiggerImage',
                'import_hyperlink_embedly_key' =>
                    'import_hyperlink_embedlyKey',
                'import_hyperlink_nbclearn_token' =>
                    'import_hyperlink_nbclearnToken'
            );

            foreach ($options as $key => $value) {
                $this->_options[$key] = get_option($value);
                delete_option($value);
            }

            $this->_installOptions();
        }
    }

    /**
     * Hook to output plugin configuration form.
     *
     * Include form from config_form.php file.
     */
    public function hookConfigForm()
    {
        include 'config_form.php';
    }

    /**
     * Hook to be performed after an item has been saved.
     *
     * @param array $args Provides record and post.
     */
    public function hookAfterSaveItem($args)
    {
        // Get the URL element from the record.
        $record = $args['record'];
        $element = $record->getElement(ElementSet::ITEM_TYPE_NAME, 'URL');

        // Get a list of all valid URLs that were checked for import.
        $urls = array();

        if (!empty($args['post']['Elements'][$element->id])) {
            foreach ($args['post']['Elements'][$element->id] as $input) {
                if (!empty($input['import']) && !empty($input['text'])) {
                    if (filter_var($input['text'], FILTER_VALIDATE_URL)) {
                        $urls[] = $input['text'];
                    }
                }
            }
        }

        // Get options for the Embed class.
        $options = array(
            'custom_adapters_namespace' => 'ImportHyperlink\\'
        );

        $preg = '/^import_hyperlink_/';

        foreach (array_keys($this->_options) as $option) {
            if (preg_match($preg, $option)) {
                $key = preg_replace($preg, '', $option);
                $options[$key] = get_option($option);
            }
        }

        // Create an array of element texts for the item.
        $texts = array();

        // Create a dispatcher that doesn't verify peers.
        $dispatcher = new Embed\Http\CurlDispatcher([
            CURLOPT_SSLVERSION => 6
        ]);

        foreach ($urls as $url) {
            // Get the embed object for each URL provided.
            $embed = Embed\Embed::create($url, $options, $dispatcher);

            // Store the Title and Description if available.
            if ($embed->title) {
                $texts['Dublin Core']['Title'][] = array(
                    'text' => $embed->title,
                    'html' => false
                );
            }

            if ($embed->description) {
                $texts['Dublin Core']['Description'][] = array(
                    'text' => $embed->description,
                    'html' => false
                );
            }

            // Store the Creator name, and link to their URL if available.
            if ($embed->authorName) {
                if ($embed->authorUrl) {
                    $texts['Dublin Core']['Creator'][] = array(
                        'text' =>
                            '<a href="'. $embed->authorUrl. '">'.
                            $embed->authorName. '</a>',
                        'html' => true
                    );
                } else {
                    $texts['Dublin Core']['Creator'][] = array(
                        'text' => $embed->authorName,
                        'html' => false
                    );
                }
            }

            // Store the Publisher name, and link to their URL if available.
            if ($embed->type != 'link' && $embed->providerName) {
                if ($embed->providerUrl) {
                    $texts['Dublin Core']['Publisher'][] = array(
                        'text' =>
                            '<a href="'. $embed->providerUrl. '">'.
                            $embed->providerName. '</a>',
                        'html' => true
                    );
                } else {
                    $texts['Dublin Core']['Publisher'][] = array(
                        'text' => $embed->providerName,
                        'html' => false
                    );
                }
            }

            // Replace the submitted URL with the canonical URL if available.
            if ($embed->url) {
                $texts[ElementSet::ITEM_TYPE_NAME]['URL'][] = array(
                    'text' => $embed->url,
                    'html' => false
                );
            }

            // Store the embed code as the Content if available.
            if ($embed->code) {
                $texts[ElementSet::ITEM_TYPE_NAME]['Content'][] = array(
                    'text' => preg_replace('{https?://}', '//', $embed->code),
                    'html' => true
                );
            }
        }

        // Save each element in an element type that was provided.
        foreach (array_keys($texts) as $set) {
            foreach (array_keys($texts[$set]) as $name) {
                // Get the element, and remove any existing texts for the item.
                $element = $record->getElement($set, $name);
                $record->deleteElementTextsByElementId(array($element->id));

                // Add all of the texts obtained from the hyperlink.
                foreach ($texts[$set][$name] as $item) {
                    $text = new ElementText();
                    $text->element_id = $element->id;
                    $text->text = !empty($item['text']) ? $item['text'] : '';
                    $text->html = !empty($item['html']);
                    $text->record_type = 'Item';
                    $text->record_id = $record->id;
                    $text->save();
                }
            }
        }

        // Check that the URL was not a "link" type, and an image is available.
        if (!empty($embed) && $embed->type != 'link' && $embed->image) {
            // Delete all existing files for the item.
            foreach ($record->getFiles() as $file) {
                $file->delete();
            }

            // Add the image as a file to the item.
            insert_files_for_item(
                $record,
                'Url',
                array(
                    'source' => $embed->image,
                    'metadata' => array(
                        'Dublin Core' => array(
                            'Source' => array(
                                array(
                                    'text' => $embed->url ? $embed->url : $url,
                                    'html' => false
                                )
                            )
                        )
                    )
                ),
                array('ignore_invalid_files' => false)
            );
        }
    }

    /**
     * Filters Exhibits to display Hyperlink content for attachments.
     *
     * @param string $html HTML of the attachment.
     * @param array $args Provides attachment and forceImage.
     *
     * @return string HTML of the attachment.
     */
    public function filterExhibitAttachmentMarkup($html, $args)
    {
        $attachment = $args['attachment'];

        // If an image is not being forced, attempt to get the content.
        if (!$args['forceImage']) {
            $item = $attachment->getItem();

            if ($item) {
                $data = metadata(
                    $attachment->getItem(),
                    array(ElementSet::ITEM_TYPE_NAME, 'Content')
                );
            }
        }

        // If we got content, set the HTML as the content with a caption.
        if (!empty($data)) {
            $html = $data. get_view()->exhibitAttachmentCaption($attachment);
        }

        return $html;
    }

    /**
     * Adds a checkbox to import a URL element.
     *
     * @param array $components Provides html_checkbox.
     * @param array $args Provides input_name_step.
     *
     * @return array $components with the addition of an import checkbox.
     */
    public function addImportCheckboxToUrlElement($components, $args)
    {
        $components['html_checkbox'] .=
            '<label class="use-html">'.
            __('Import').
            get_view()->formCheckbox($args['input_name_stem']. '[import]', 1).
            '</label>';

        return $components;
    }
}
