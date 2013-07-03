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

/**
 * Find nodes based on a fulltext search
 *
 * @Flow\Scope("prototype")
 */
class NodeSearchService {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Factory\NodeFactory
	 */
	protected $nodeFactory;

	/**
	 * Search all properties for given $term
	 * @param string $term
	 * @param array $searchNodeTypes
	 * @param \TYPO3\TYPO3CR\Domain\Service\ContextInterface $context
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeData>
	 */
	public function findByProperties($term, array $searchNodeTypes = array(), \TYPO3\TYPO3CR\Domain\Service\ContextInterface $context) {
			// TODO: Implement a better search when Flow offer the possibility
		$query = $this->nodeDataRepository->createQuery();
		$constraints = array($query->like('properties', '%' . $term . '%'));

		if ($searchNodeTypes !== array()) {
			$constraints[] = $query->in('nodeType', $searchNodeTypes);
		}

		$searchResult = array();
		foreach ($query->matching($query->logicalAnd($constraints))->execute() as $nodeData) {
			if (array_key_exists($nodeData->getPath(), $searchResult) === FALSE) {
				$node = $this->nodeFactory->createFromNodeData($nodeData, $context);
				if ($node !== NULL) {
					$searchResult[$node->getPath()] = $node;
				}
			}
		}

		return $searchResult;
	}
}
