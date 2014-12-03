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
		$nodeTypeFilter = implode(',', $searchNodeTypes);
		$nodeDataRecords = $this->nodeDataRepository->findByProperties($term, $nodeTypeFilter, $context->getWorkspace(), $context->getDimensions());
		foreach ($nodeDataRecords as $nodeData) {
			$node = $this->nodeFactory->createFromNodeData($nodeData, $context);
			if ($node !== NULL) {
				$searchResult[$node->getPath()] = $node;
			}
		}

		return $searchResult;
	}

}
