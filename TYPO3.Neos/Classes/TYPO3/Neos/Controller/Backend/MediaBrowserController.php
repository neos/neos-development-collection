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
use TYPO3\Flow\Configuration\ConfigurationManager;

/**
 * Controller for asset handling
 */
class MediaBrowserController extends \TYPO3\Media\Controller\AssetController {

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var ConfigurationManager
	 * @Flow\Inject
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\Media\Domain\Repository\AssetRepository
	 * @Flow\Inject
	 */
	protected $assetRepository;

	/**
	 * @return void
	 */
	public function initializeAction() {
		parent::initializeAction();
		$this->settings = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Media');
	}

}