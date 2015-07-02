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
use TYPO3\TYPO3CR\Domain\Model\NodeType;

/**
 * The TYPO3 Module
 *
 * @Flow\Scope("singleton")
 */
class SchemaController extends ActionController {

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
	 * @return string
	 */
	public function nodeTypeSchemaAction() {
		$this->response->setHeader('Content-Type', 'application/json');

		$schema = array('inheritanceMap' => array('subTypes' => array()), 'nodeTypes' => array());

		$nodeTypes = $this->nodeTypeManager->getNodeTypes(TRUE);
		/** @var NodeType $nodeType */
		foreach ($nodeTypes as $nodeTypeName => $nodeType) {
			if ($nodeType->isAbstract() === FALSE) {
				$configuration = $nodeType->getFullConfiguration();
				$this->flattenAlohaFormatOptions($configuration);
				$schema['nodeTypes'][$nodeTypeName] = $configuration;
			}

			$schema['inheritanceMap']['subTypes'][$nodeTypeName] = array();
			foreach ($this->nodeTypeManager->getSubNodeTypes($nodeType->getName(), TRUE) as $subNodeType) {
				/** @var NodeType $subNodeType */
				$schema['inheritanceMap']['subTypes'][$nodeTypeName][] = $subNodeType->getName();
			}
		}

		return json_encode($schema);
	}

	/**
	 * @param array $nodeTypes
	 * @return array
	 */
	protected function getNonAbstractNodeTypes(array $nodeTypes) {
		return array_filter(
			$nodeTypes,
			function (NodeType $nodeType) {
				return !$nodeType->isAbstract();
			}
		);
	}

	/**
	 * In order to allow unsetting options via the YAML settings merging, the
	 * formatting options can be set via 'option': TRUE, however, the frontend
	 * schema expects a flattened plain numeric array. This methods adjust the setting
	 * accordingly.
	 *
	 * @param array $options The options array, passed by reference
	 * @return void
	 */
	protected function flattenAlohaFormatOptions(array &$options) {
		if (isset($options['properties'])) {
			foreach (array_keys($options['properties']) as $propertyName) {
				if (isset($options['properties'][$propertyName]['ui']['aloha'])) {
					foreach ($options['properties'][$propertyName]['ui']['aloha'] as $formatGroup => $settings) {
						$flattenedSettings = array();
						foreach ($settings as $key => $option) {
							if (is_numeric($key) && is_string($option)) {
								$flattenedSettings[] = $option;
							} elseif ($option === TRUE) {
								$flattenedSettings[] = $key;
							}
						}
						$options['properties'][$propertyName]['ui']['aloha'][$formatGroup] = $flattenedSettings;
					}
				}
			}
		}
	}
}
