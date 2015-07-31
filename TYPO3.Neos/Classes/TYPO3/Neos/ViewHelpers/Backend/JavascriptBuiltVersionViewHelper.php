<?php
namespace TYPO3\Neos\ViewHelpers\Backend;

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
 * Returns a shortened md5 of the built JavaScript file
 */
class JavascriptBuiltVersionViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Utility\BackendAssetsUtility
	 */
	protected $backendAssetsUtility;

	/**
	 * @return string
	 */
	public function render() {
		return $this->backendAssetsUtility->getJavascriptBuiltVersion();
	}

}