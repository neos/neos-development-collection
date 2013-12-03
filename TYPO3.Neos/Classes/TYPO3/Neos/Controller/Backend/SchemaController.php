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
	 * @var \TYPO3\Neos\Service\NodeTypeSchemaBuilder
	 * @Flow\Inject
	 */
	protected $schemaBuilder;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 * @Flow\Inject
	 */
	protected $nodeTypeManager;

	/**
	 * Generate and renders the JSON schema for the node types for VIE.
	 * Schema format example: http://schema.rdfs.org/all.json
	 *
	 * @return string
	 */
	public function vieSchemaAction() {
		$this->response->setHeader('Content-Type', 'application/json');

		return json_encode($this->schemaBuilder->generateVieSchema());
	}

	/**
	 * Get the node type configuration schema for the Neos UI
	 *
	 * @param string $superType
	 * @return string
	 */
	public function nodeTypeSchemaAction($superType = NULL) {
		$this->response->setHeader('Content-Type', 'application/json');

		$nodeTypes = array();
		if ($superType !== NULL) {
			foreach ($this->nodeTypeManager->getSubNodeTypes($superType) as $nodeTypeName => $nodeType) {
				/** @var \TYPO3\TYPO3CR\Domain\Model\NodeType $nodeType */
				$nodeTypes[$nodeTypeName] = $nodeType->getFullConfiguration();
			}
		} else {
			$nodeTypes = $this->nodeTypeManager->getFullConfiguration();
		}

		return json_encode($nodeTypes);
	}

}
