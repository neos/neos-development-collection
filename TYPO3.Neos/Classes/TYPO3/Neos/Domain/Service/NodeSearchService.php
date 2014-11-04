<?php
namespace TYPO3\Neos\Domain\Service;

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
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * Find nodes based on a fulltext search
 *
 * @Flow\Scope("singleton")
 */
class NodeSearchService implements NodeSearchServiceInterface {

	/**
	 * @Flow\Inject
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var NodeFactory
	 */
	protected $nodeFactory;

	/**
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Search all properties for given $term
	 *
	 * TODO: Implement a better search when Flow offer the possibility
	 *
	 * @param string $term
	 * @param array $searchNodeTypes
	 * @param Context $context
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface>
	 */
	public function findByProperties($term, array $searchNodeTypes, Context $context) {
		$searchResult = array();
		$nodeDataRecords = $this->getNodeDataRecordsByWorkspace($term, $searchNodeTypes, $context->getWorkspace());
		foreach ($nodeDataRecords as $nodeData) {
			if (array_key_exists($nodeData->getPath(), $searchResult) === FALSE) {
				$node = $this->nodeFactory->createFromNodeData($nodeData, $context);
				if ($node !== NULL) {
					$searchResult[$node->getPath()] = $node;
				}
			}
		}

		return $searchResult;
	}

	/**
	 * Returns matching nodes for the given $term, $searchNodeTypes with workspace-fallback
	 *
	 * @param string $term
	 * @param array $searchNodeTypes
	 * @param Workspace $workspace
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeData>
	 */
	protected function getNodeDataRecordsByWorkspace($term, array $searchNodeTypes, Workspace $workspace) {
		$result = array();
		while ($workspace !== NULL) {
			$workspaceQuery = $this->nodeDataRepository->createQuery();
			$nodes = $workspaceQuery->matching($workspaceQuery->logicalAnd(array(
				$workspaceQuery->equals('workspace', $workspace),
				// FIXME: This should be case insensitive (second argument FALSE) but due to properties being a blob field that doesn't work currently.
				$workspaceQuery->like('properties', '%' . $term . '%', TRUE),
				$workspaceQuery->in('nodeType', $searchNodeTypes)
			)))->execute();

			foreach ($nodes as $node) {
				/** @var \TYPO3\TYPO3CR\Domain\Model\NodeData $node */
				if (isset($result[$node->getIdentifier()])) {
					continue;
				}
				$result[$node->getIdentifier()] = $this->persistenceManager->getIdentifierByObject($workspace);
			}
			$workspace = $workspace->getBaseWorkspace();
		}

		$query = $this->nodeDataRepository->createQuery();
		$constraints = array();
		foreach ($result as $nodeIdentifier => $workspaceIdentifier) {
			$constraints[] = $query->logicalAnd(
				$query->equals('workspace', $workspaceIdentifier),
				$query->equals('identifier', $nodeIdentifier)
			);
		}
		return $query->matching($query->logicalOr($constraints))->execute()->toArray();
	}
}
