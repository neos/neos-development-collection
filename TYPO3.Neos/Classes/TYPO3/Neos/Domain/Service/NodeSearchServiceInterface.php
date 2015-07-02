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
use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * Interface for the node search service for finding nodes based on a fulltext search
 */
interface NodeSearchServiceInterface {

	/**
	 * @param string $term
	 * @param array $searchNodeTypes
	 * @param Context $context
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface>
	 */
	public function findByProperties($term, array $searchNodeTypes, Context $context);

}
