<?php
namespace TYPO3\TYPO3\Controller\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The TYPO3 Module
 *
 * @FLOW3\Scope("singleton")
 */
class SchemaController extends \TYPO3\FLOW3\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 * @FLOW3\Inject
	 */
	protected $configurationManager;

	/**
	 * Generate and renders the JSON schema for the content types.
	 * Schema format example: http://schema.rdfs.org/all.json
	 *
	 * @return string
	 */
	public function indexAction() {
		$this->response->setHeader('Content-Type', 'application/json');

		$configuration = $this->configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.TYPO3CR');
		$schemaBuilder = new \TYPO3\TYPO3\Service\ContentTypeSchemaBuilder($configuration);
		$schemaBuilder->convertToVieSchema();
		return $schemaBuilder->generateAsJson();
	}

}
?>