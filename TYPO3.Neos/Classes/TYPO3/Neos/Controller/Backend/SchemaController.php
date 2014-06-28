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
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Neos\Service\NodeTypeSchemaBuilder;
use TYPO3\Neos\Service\VieSchemaBuilder;

/**
 * The TYPO3 Module
 *
 * @Flow\Scope("singleton")
 */
class SchemaController extends ActionController {

	/**
	 * @var VieSchemaBuilder
	 * @Flow\Inject
	 */
	protected $vieSchemaBuilder;

	/**
	 * @var NodeTypeSchemaBuilder
	 * @Flow\Inject
	 */
	protected $nodeTypeSchemaBuilder;

	/**
	 * Generate and renders the JSON schema for the node types for VIE.
	 * Schema format example: http://schema.rdfs.org/all.json
	 *
	 * @return string
	 */
	public function vieSchemaAction() {
		$this->response->setHeader('Content-Type', 'application/json');

		return json_encode($this->vieSchemaBuilder->generateVieSchema());
	}

	/**
	 * Get the node type configuration schema for the Neos UI
	 *
	 * @return string
	 */
	public function nodeTypeSchemaAction() {
		$this->response->setHeader('Content-Type', 'application/json');

		return json_encode($this->nodeTypeSchemaBuilder->generateNodeTypeSchema());
	}
}
