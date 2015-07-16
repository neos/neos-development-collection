<?php
namespace TYPO3\Neos\Controller;

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
 * A trait to add backend translation based on the backend users settings
 */
trait BackendUserTranslationTrait {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\I18n\Service
	 */
	protected $_localizationService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Service\UserService
	 */
	protected $_userService;

	/**
	 * Set the locale according to the user settings
	 *
	 * @return void
	 */
	protected function initializeObject() {
		$this->_localizationService->getConfiguration()->setCurrentLocale(new \TYPO3\Flow\I18n\Locale($this->_userService->getInterfaceLanguage()));
	}
}