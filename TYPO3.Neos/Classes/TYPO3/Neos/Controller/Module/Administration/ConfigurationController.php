<?php
namespace TYPO3\Neos\Controller\Module\Administration;

/*                                                                        *
 * This script belongs to the Flow package "TYPO3.Neos".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use TYPO3\Flow\Error\Message;

/**
 * The TYPO3 Neos Configuration module controller
 */
class ConfigurationController extends AbstractModuleController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @Flow\Inject(lazy = FALSE)
	 * @var \TYPO3\Flow\Configuration\ConfigurationSchemaValidator
	 */
	protected $configurationSchemaValidator;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\SchemaGenerator
	 */
	protected $schemaGenerator;

	/**
	 * @param string $type
	 * @return void
	 */
	public function indexAction($type = 'Settings') {
		$availableConfigurationTypes = $this->configurationManager->getAvailableConfigurationTypes();
		$this->view->assignMultiple(array(
			'type' => $type,
			'availableConfigurationTypes' => $availableConfigurationTypes
		));

		if (in_array($type, $availableConfigurationTypes)) {
			$this->view->assign('configuration', $this->configurationManager->getConfiguration($type));

			try {
				$this->view->assign('validationResult', $this->configurationSchemaValidator->validate($type));
			} catch (\TYPO3\Flow\Configuration\Exception\SchemaValidationException $exception) {
				$this->addFlashMessage($exception->getMessage(), 'An error occurred during validation of the configuration.', Message::SEVERITY_ERROR, array(), 1412373972);
			}
		} else {
			$this->addFlashMessage('Configuration type not found.', '', Message::SEVERITY_ERROR, array(), 1412373998);
		}
	}

}