<?php
namespace TYPO3\Neos\Utility;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * A collection of helper methods for the Neos backend assets
 */
class BackendAssetsUtility
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Returns TRUE if the minified Neos JavaScript sources should be loaded, FALSE otherwise.
     *
     * @return boolean
     */
    public function shouldLoadMinifiedJavascript()
    {
        return isset($this->settings['userInterface']['loadMinifiedJavaScript']) ? $this->settings['userInterface']['loadMinifiedJavaScript'] : $this->settings['userInterface']['loadMinifiedJavascript'];
    }

    /**
     * Returns a shortened md5 of the built JavaScript file
     *
     * @return string
     */
    public function getJavascriptBuiltVersion()
    {
        return substr(md5_file('resource://TYPO3.Neos/Public/JavaScript/ContentModule-built.js'), 0, 12);
    }

    /**
     * Returns a shortened md5 of the built CSS file
     *
     * @return string
     */
    public function getCssBuiltVersion()
    {
        return substr(md5_file('resource://TYPO3.Neos/Public/Styles/Includes-built.css'), 0, 12);
    }
}
