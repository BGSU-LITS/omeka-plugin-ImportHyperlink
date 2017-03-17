<?php
/**
 * Omeka Import Hyperlink Plugin: NBC Learn Embed Adapter
 *
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2015 Bowling Green State University Libraries
 * @license MIT
 */

namespace Embed\Adapters;

use Embed\Request;
use Embed\Viewers;

/**
 * Omeka Import Hyperlink Plugin: NBC Learn Embed Adapter Plugin Class
 *
 * @package Import Hyperlink
 */
class Nbclearn extends Webpage implements AdapterInterface
{
    /**
     * Checks whether the request is valid to this Adapter
     *
     * @param Request $request
     *
     * @return boolean
     */
    public static function check(Request $request)
    {
        return $request->match(array(
            'http://highered.nbclearn.com/*'
        ));
    }

    /**
     * Gets the type of the url
     * The types are the same than the oEmbed types:
     * video, photo, link, rich
     *
     * @return string|null
     */
    public function getType()
    {
        return 'video';
    }

    /**
     * Gets the embed code
     *
     * @return string|null
     */
    public function getCode()
    {
        // A cuecard ID is needed to embed an iframe.
        $url = $this->getUrl();

        if (preg_match('/[?&]cuecard=(\w+)/', $url, $matches)) {
            // Default to a fake token if a real one is not available:
            $token = $this->options['nbclearnToken']
                ? $this->options['nbclearnToken']
                : 'X';

            $url = 'https://highered.nbclearn.com/portal/site/root/widget/'.
                $token. '/'. $matches[1];

            return Viewers::iframe($url, $this->width, $this->height);
        }

        return parent::getCode();
    }

    /**
     * Gets the canonical url
     *
     * @return string|null
     */
    public function getUrl()
    {
        $url = parent::getUrl();

        if (preg_match('/[?&]cuecard=(\w+)/', $url, $matches)) {
            $url = 'http://highered.nbclearn.com/portal/site/HigherEd/browse'.
                '?cuecard='. $matches[1];
        }

        return $url;
    }

    /**
     * Gets the provider name
     *
     * @return string|null
     */
    public function getProviderName()
    {
        return 'NBC Learn';
    }

    /**
     * Gets the provider url (usually the home url of the link)
     *
     * @return string|null
     */
    public function getProviderUrl()
    {
        return 'http://highered.nbclearn.com';
    }

    /**
     * Gets the width of the embedded widget
     *
     * @return integer|null
     */
    public function getWidth()
    {
        return 500;
    }

    /**
     * Gets the height of the embedded widget
     *
     * @return integer|null
     */
    public function getHeight()
    {
        return 390;
    }

    /**
     * Initializes all providers used in this adapter (oembed, opengraph, etc)
     *
     * @param Request $request
     */
    protected function initProviders(Request $request)
    {
        // If possible, use the flatview instead, as it has the right image.
        $url = $request->url->getUrl();

        if (preg_match('/[?&]cuecard=(\w+)/', $url, $matches)) {
            // Only use a token if one is available.
            $token = $this->options['nbclearnToken']
                ? '&token='. $this->options['nbclearnToken']
                : '';

            $url =
                'http://highered.nbclearn.com/portal/site/HigherEd/flatview'.
                '?cuecard='. $matches[1]. $token;

            $request = $request->createRequest($url);
        }

        parent::initProviders($request);
    }
}
