<?php
namespace Neos\Neos\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * Find nodes based on a fulltext search
 *
 * @Flow\Scope("singleton")
 */
class NodeSearchService implements NodeSearchServiceInterface
{
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
     * @param string|array $term search term
     * @param array $searchNodeTypes
     * @param Context $context
     * @param NodeInterface $startingPoint
     * @return array <\TYPO3\TYPO3CR\Domain\Model\NodeInterface>
     */
    public function findByProperties($term, array $searchNodeTypes, Context $context, NodeInterface $startingPoint = null)
    {
        if (empty($term)) {
            throw new \InvalidArgumentException('"term" cannot be empty: provide a term to search for.', 1421329285);
        }
        $searchResult = array();
        $nodeTypeFilter = implode(',', $searchNodeTypes);
        $nodeDataRecords = $this->nodeDataRepository->findByProperties($term, $nodeTypeFilter, $context->getWorkspace(), $context->getDimensions(), $startingPoint ? $startingPoint->getPath() : null);
        foreach ($nodeDataRecords as $nodeData) {
            $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
            if ($node !== null) {
                $searchResult[$node->getPath()] = $node;
            }
        }

        return $searchResult;
    }
}
