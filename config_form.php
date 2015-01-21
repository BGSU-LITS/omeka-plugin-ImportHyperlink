<?php
/**
 * Omeka Import Hyperlink Plugin: Configuration Form
 *
 * Outputs the configuration form for the config_form hook.
 *
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2015 Bowling Green State University Libraries
 * @license MIT
 * @package Import Hyperlink
 */

$sections = array(
    'Images' => array(
        array(
            'name' => 'import_hyperlink_minImageWidth',
            'label' => __('Minimum Image Width'),
            'explanation' => __(
                'When choosing an image to represent the hyperlink, the image'.
                ' must at least be this wide.'
            )
        ),
        array(
            'name' => 'import_hyperlink_minImageHeight',
            'label' => __('Minimum Image Height'),
            'explanation' => __(
                'When choosing an image to represent the hyperlink, the image'.
                ' must at least be this high.'
            )
        ),
        array(
            'name' => 'import_hyperlink_getBiggerImage',
            'label' => __('Use Largest Image'),
            'checkbox' => true,
            'explanation' => __(
                'By default, the first image found matching the requirements'.
                ' above will be used. If checked, the largest image will be'.
                ' used instead.'
            )
        )
    ),
    'Services' => array(
        array(
            'name' => 'import_hyperlink_embedlyKey',
            'label' => __('Embedly API Key'),
            'explanation' => __(
                'If provided, Embedly will be used as a fallback service if'.
                ' the hyperlink otherwise could not be embedded.'
            )
        ),
        array(
            'name' => 'import_hyperlink_nbclearnToken',
            'label' => __('NBC Learn Token'),
            'explanation' => __(
                'Must be provided to embed full NBC Learn videos. The token'.
                ' can be determined by inspecting videos embedded with a LMS.'
            )
        )
    )
);
?>

<?php foreach ($sections as $section => $fields): ?>
    <h2><?php echo $section; ?></h2>

    <?php foreach ($fields as $field): ?>
        <div class="field">
            <div class="two columns alpha">
                <label for="<?php echo $field['name']; ?>">
                    <?php echo $field['label']; ?>
                </label>
            </div>
            <div class="inputs five columns omega">
                <?php if (isset($field['select'])): ?>
                    <select name="<?php echo $field['name']; ?>" id="<?php echo $field['name']; ?>">
                        <?php foreach ($field['select'] as $value => $option): ?>
                            <option value="<?php echo $value; ?>"<?php if (get_option($field['name']) == $value) echo ' selected'; ?>>
                                <?php echo $option; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif (isset($field['checkbox'])): ?>
                    <input type="hidden" name="<?php echo $field['name']; ?>" value="">
                    <input type="checkbox" name="<?php echo $field['name']; ?>" id="<?php echo $field['name']; ?>" value="<?php echo $field['checkbox']; ?>"<?php if (get_option($field['name']) == $field['checkbox']) echo ' checked'; ?>>
                <?php else: ?>
                    <input type="<?php print(empty($field['password']) ? 'text' : 'password'); ?>" name="<?php echo $field['name']; ?>" id="<?php echo $field['name']; ?>" value="<?php echo get_option($field['name']); ?>">
                <?php endif; ?>

                <?php if (isset($field['explanation'])): ?>
                    <p class="explanation">
                        <?php echo $field['explanation']; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endforeach; ?>
