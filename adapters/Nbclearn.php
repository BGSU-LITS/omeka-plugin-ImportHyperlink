<?php
/**
 * Omeka Import Hyperlink Plugin: NBC Learn Embed Adapter
 *
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2018 Bowling Green State University Libraries
 * @license MIT
 */

namespace ImportHyperlink;

/**
 * Omeka Import Hyperlink Plugin: NBC Learn Embed Adapter Plugin Class
 *
 * @package Import Hyperlink
 */
class Nbclearn extends \Embed\Adapters\Webpage
{
    /**
     * {@inheritdoc}
     */
    public static function check(\Embed\Http\Response $response)
    {
        return $response->isValid() && $response->getUrl()->match(array(
            'highered.nbclearn.com/*'
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        // If available, load the flat view to get the correct image.
        $url = $this->getResponse()->getStartingUrl();

        if (preg_match('/[?&]cuecard=(\w+)/', $url, $matches)) {
            // Only use a token if one is available.
            $token = $this->options['nbclearn_token']
                ? '&token='. $this->options['nbclearn_token']
                : '';

            $url = \Embed\Http\Url::create(
                'http://highered.nbclearn.com/portal/site/HigherEd/flatview'.
                '?cuecard='. $matches[1]. $token
            );

            $this->response = $this->getDispatcher()->dispatch($url);
        }

        parent::init();
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        // A cuecard ID is needed to embed an iframe.
        if (preg_match('/[?&]cuecard=(\w+)/', $this->url, $matches)) {
            // Default to a fake token if a real one is not available:
            $token = $this->options['nbclearn_token']
                ? $this->options['nbclearn_token']
                : 'X';

            $url = 'https://highered.nbclearn.com/portal/site/root/widget/'.
                $token. '/'. $matches[1];

            return \Embed\Utils::iframe($url, $this->width, $this->height);
        }

        return parent::getCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        // Canonicalize the URL to the regular website.
        $url = parent::getUrl();

        if (preg_match('/[?&]cuecard=(\w+)/', $url, $matches)) {
            $url = 'http://highered.nbclearn.com/portal/site/HigherEd/browse'.
                '?cuecard='. $matches[1];
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getWidth()
    {
        return 500;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeight()
    {
        return 390;
    }
}
