<?php
namespace TYPO3\Neos\Domain\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * Interface for the node search service for finding nodes based on a fulltext search
 */
interface NodeSearchServiceInterface
{
    /**
     * @param string $term
     * @param array $searchNodeTypes
     * @param Context $context
     * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface>
     */
    public function findByProperties($term, array $searchNodeTypes, Context $context);
}
