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

use Neos\ContentRepository\Validation\Validator\NodeIdentifierValidator;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * Find nodes based on a fulltext search
 *
 * @Flow\Scope("singleton")
 */
class NodeSearchService implements NodeSearchServiceInterface
{
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
     * @param NodeInterface $startingPoint
     * @return array <\Neos\ContentRepository\Domain\Model\NodeInterface>
     */
    public function findByProperties($term, array $searchNodeTypes, NodeInterface $startingPoint = null)
    {
        if (empty($term)) {
            throw new \InvalidArgumentException('"term" cannot be empty: provide a term to search for.', 1421329285);
        }

        $searchResult = [];
        $nodeTypeFilter = implode(',', $searchNodeTypes);

        $searchTerm = is_string($term) ? [$term] : $term;

        foreach ($searchTerm as $termvalue) {
            if (preg_match(NodeIdentifierValidator::PATTERN_MATCH_NODE_IDENTIFIER, $termvalue) !== 0) {
                $nodeByIdentifier = $context->getNodeByIdentifier($termvalue);
                if ($nodeByIdentifier !== null && $this->nodeSatisfiesSearchNodeTypes($nodeByIdentifier, $searchNodeTypes)) {
                    $searchResult[$nodeByIdentifier->getPath()] = $nodeByIdentifier;
                }
            }
        }

        $nodeDataRecords = $this->nodeDataRepository->findByProperties($term, $nodeTypeFilter, $context->getWorkspace(), $context->getDimensions(), $startingPoint ? $startingPoint->getPath() : null);
        foreach ($nodeDataRecords as $nodeData) {
            $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
            if ($node !== null) {
                $searchResult[$node->getPath()] = $node;
            }
        }

        return $searchResult;
    }

    /**
     * Whether or not the given $node satisfies the specified types
     *
     * @param NodeInterface $node
     * @param array $searchNodeTypes
     * @return bool
     */
    protected function nodeSatisfiesSearchNodeTypes(NodeInterface $node, array $searchNodeTypes): bool
    {
        foreach ($searchNodeTypes as $nodeTypeName) {
            if ($node->getNodeType()->isOfType($nodeTypeName)) {
                return true;
            }
        }
        return false;
    }
}
