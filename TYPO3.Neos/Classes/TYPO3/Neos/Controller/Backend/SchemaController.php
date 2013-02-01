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

/**
 * The TYPO3 Module
 *
 * @Flow\Scope("singleton")
 */
class SchemaController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 * @Flow\Inject
	 */
	protected $configurationManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * Generate and renders the JSON schema for the node types for VIE.
	 * Schema format example: http://schema.rdfs.org/all.json
	 *
	 * @return string
	 * @Flow\SkipCsrfProtection
	 */
	public function vieSchemaAction() {
		$this->response->setHeader('Content-Type', 'application/json');

		$configuration = $this->configurationManager->getConfiguration('NodeTypes');
		$schemaBuilder = new \TYPO3\Neos\Service\NodeTypeSchemaBuilder($configuration);
		$schemaBuilder->convertToVieSchema();
		return $schemaBuilder->generateAsJson();
	}

	/**
	 * Get the node type configuration schema for the Neos UI
	 *
	 * @return string
	 * @Flow\SkipCsrfProtection
	 */
	public function nodeTypeSchemaAction() {
		$this->response->setHeader('Content-Type', 'application/json');

		return json_encode($this->nodeTypeManager->getFullConfiguration());
	}

}
?>