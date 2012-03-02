<?php
namespace TYPO3\TYPO3\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Find nodes based on a fulltext search
 *
 * @FLOW3\Scope("prototype")
 */
class NodeSearchService {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * Search all properties for given $term
	 * @param string $term
	 * @param array $searchContentTypes
	 * @return type
	 */
	public function findByProperties($term, array $searchContentTypes) {
			// TODO: Implement a better search when FLOW3 offer the possibility
		$query = $this->nodeRepository->createQuery();
		$constraints = array(
			$query->like('properties', '%' . $term . '%'),
			$query->in('contentType', $searchContentTypes)
		);
		$results = $query->matching($query->logicalAnd($constraints))->execute();

		return $results;
	}
}
?>