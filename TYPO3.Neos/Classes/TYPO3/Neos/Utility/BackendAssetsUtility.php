<?php
namespace TYPO3\Neos\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A collection of helper methods for the Neos backend assets
 */
class BackendAssetsUtility {

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Returns TRUE if the minified Neos JavaScript sources should be loaded, FALSE otherwise.
	 *
	 * @return boolean
	 */
	public function shouldLoadMinifiedJavascript() {
		return isset($this->settings['userInterface']['loadMinifiedJavaScript']) ? $this->settings['userInterface']['loadMinifiedJavaScript'] : $this->settings['userInterface']['loadMinifiedJavascript'];
	}

	/**
	 * Returns a shortened md5 of the built JavaScript file
	 *
	 * @return string
	 */
	public function getJavascriptBuiltVersion() {
		return substr(md5_file('resource://TYPO3.Neos/Public/JavaScript/ContentModule-built.js'), 0, 12);
	}

	/**
	 * Returns a shortened md5 of the built CSS file
	 *
	 * @return string
	 */
	public function getCssBuiltVersion() {
		return substr(md5_file('resource://TYPO3.Neos/Public/Styles/Includes-built.css'), 0, 12);
	}

}