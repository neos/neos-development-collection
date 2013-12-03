<?php
namespace TYPO3\TYPO3CR\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Manager for node types
 *
 * @Flow\Scope("singleton")
 * @api
 */
class NodeTypeManager {

	/**
	 * Node types, indexed by name
	 *
	 * @var array
	 */
	protected $cachedNodeTypes = array();

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * Return all non-abstract node types which have a certain $superType, without
	 * the $superType itself.
	 *
	 * @param string $superTypeName
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeType> all node types registered in the system
	 */
	public function getSubNodeTypes($superTypeName) {
		if ($this->cachedNodeTypes === array()) {
			$this->loadNodeTypes();
		}

		$filteredNodeTypes = array();
		foreach ($this->cachedNodeTypes as $nodeTypeName => $nodeType) {
			if (!$nodeType->isAbstract() && $nodeType->isOfType($superTypeName) && $nodeTypeName !== $superTypeName) {
				$filteredNodeTypes[$nodeTypeName] = $nodeType;
			}
		}
		return $filteredNodeTypes;
	}

	/**
	 * Returns the specified node type, if it is not abstract.
	 *
	 * @param string $nodeTypeName
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeType or NULL
	 * @throws \TYPO3\TYPO3CR\Exception\NodeTypeNotFoundException
	 * @throws \TYPO3\TYPO3CR\Exception\NodeTypeIsAbstractException
	 * @api
	 */
	public function getNodeType($nodeTypeName) {
		if ($this->cachedNodeTypes === array()) {
			$this->loadNodeTypes();
		}
		if (!isset($this->cachedNodeTypes[$nodeTypeName])) {
			throw new \TYPO3\TYPO3CR\Exception\NodeTypeNotFoundException('The node type "' . $nodeTypeName . '" is not available.', 1316598370);
		}
		if ($this->cachedNodeTypes[$nodeTypeName]->isAbstract()) {
			throw new \TYPO3\TYPO3CR\Exception\NodeTypeIsAbstractException('The node type "' . $nodeTypeName . '" is marked abstract and cannot be fetched.', 1316598370);
		}
		return $this->cachedNodeTypes[$nodeTypeName];
	}

	/**
	 * Checks if the specified node type exists and is not abstract.
	 *
	 * @param string $nodeTypeName Name of the node type
	 * @return boolean TRUE if it exists, otherwise FALSE
	 * @api
	 */
	public function hasNodeType($nodeTypeName) {
		if ($this->cachedNodeTypes === array()) {
			$this->loadNodeTypes();
		}
		return isset($this->cachedNodeTypes[$nodeTypeName]) && !$this->cachedNodeTypes[$nodeTypeName]->isAbstract();
	}

	/**
	 * Creates a new node type
	 *
	 * @param string $nodeTypeName Unique name of the new node type. Example: "TYPO3.Neos:Page"
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeType
	 * @throws \TYPO3\TYPO3CR\Exception
	 */
	public function createNodeType($nodeTypeName) {
		throw new \TYPO3\TYPO3CR\Exception('Creation of node types not supported so far; tried to create "' . $nodeTypeName . '".', 1316449432);
	}

	/**
	 * Return the full configuration of all non-abstract node types. This is just an internal
	 * method we need for exporting the schema to JavaScript for example.
	 *
	 * @return array
	 */
	public function getFullConfiguration() {
		if ($this->cachedNodeTypes === array()) {
			$this->loadNodeTypes();
		}
		$fullConfiguration = array();
		foreach ($this->cachedNodeTypes as $nodeTypeName => $nodeType) {
			if (!$nodeType->isAbstract()) {
				$fullConfiguration[$nodeTypeName] = $nodeType->getFullConfiguration();
			}
		}
		return $fullConfiguration;
	}

	/**
	 * Loads all node types into memory.
	 *
	 * @return void
	 */
	protected function loadNodeTypes() {
		$completeNodeTypeConfiguration = $this->configurationManager->getConfiguration('NodeTypes');
		foreach (array_keys($completeNodeTypeConfiguration) as $nodeTypeName) {
			$this->loadNodeType($nodeTypeName, $completeNodeTypeConfiguration);
		}
	}

	/**
	 * Load one node type, if it is not loaded yet.
	 *
	 * @param string $nodeTypeName
	 * @param array $completeNodeTypeConfiguration the full node type configuration for all node types
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeType
	 * @throws \TYPO3\TYPO3CR\Exception
	 */
	protected function loadNodeType($nodeTypeName, array $completeNodeTypeConfiguration) {
		if (isset($this->cachedNodeTypes[$nodeTypeName])) {
			return $this->cachedNodeTypes[$nodeTypeName];
		}

		if (!isset($completeNodeTypeConfiguration[$nodeTypeName])) {
			throw new \TYPO3\TYPO3CR\Exception('Node type "' . $nodeTypeName . '" does not exist', 1316451800);
		}

		$nodeTypeConfiguration = $completeNodeTypeConfiguration[$nodeTypeName];

		$mergedConfiguration = array();
		$superTypes = array();
		if (isset($nodeTypeConfiguration['superTypes'])) {
			foreach ($nodeTypeConfiguration['superTypes'] as $superTypeName) {
				$superType = $this->loadNodeType($superTypeName, $completeNodeTypeConfiguration);
				if ($superType->isFinal() === TRUE) {
					throw new \TYPO3\TYPO3CR\Exception\NodeTypeIsFinalException('Node type "' . $nodeTypeName . '" has a supertype "' . $superType->getName() .'" which is final.', 1316452423);
				}
				$superTypes[] = $superType;
				$mergedConfiguration = \TYPO3\Flow\Utility\Arrays::arrayMergeRecursiveOverrule($mergedConfiguration, $superType->getFullConfiguration());
			}
			unset($mergedConfiguration['superTypes']);
		}
		$mergedConfiguration = \TYPO3\Flow\Utility\Arrays::arrayMergeRecursiveOverrule($mergedConfiguration, $nodeTypeConfiguration);

		$nodeType = new \TYPO3\TYPO3CR\Domain\Model\NodeType($nodeTypeName, $superTypes, $mergedConfiguration);

		$this->cachedNodeTypes[$nodeTypeName] = $nodeType;
		return $nodeType;
	}
}
