<?php
namespace TYPO3\Neos\Controller\Backend;

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
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\PositionalArraySorter;

/**
 * @Flow\Scope("singleton")
 */
class SettingsController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @return string
	 */
	public function editPreviewAction() {
		$this->response->setHeader('Content-Type', 'application/json');
		$configuration = new PositionalArraySorter(Arrays::getValueByPath($this->settings, 'userInterface.editPreviewModes'));
		return json_encode($configuration->toArray());
	}

}
