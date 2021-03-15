<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\InMemoryCache;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\SearchTerm;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\EventSourcedContentRepository\Domain\Context\ContentSubgraph\SubtreeInterface;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraints;
use Neos\Flow\Annotations as Flow;

/**
 * The content subgraph application repository
 *
 * To be used as a read-only source of nodes.
 *
 * ## Conventions for SQL queries
 *
 * - n -> node
 * - h -> hierarchy hyperrelation
 *
 * - if more than one node (parent-child)
 *   - pn -> parent node
 *   - cn -> child node
 *   - h -> the hierarchy hyperrelation connecting parent and children
 *   - ph -> the hierarchy hyperrelation incoming to the parent (sometimes relevant)
 *
 *
 * @api
 */
final class ContentSubhypergraph implements ContentSubgraphInterface
{
    public function findChildNodes(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): array {
        // TODO: Implement findChildNodes() method.
    }

    public function findReferencedNodes(
        NodeAggregateIdentifier $nodeAggregateAggregateIdentifier,
        PropertyName $name = null
    ): array {
        // TODO: Implement findReferencedNodes() method.
    }

    public function findReferencingNodes(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        PropertyName $name = null
    ): array {
        // TODO: Implement findReferencingNodes() method.
    }

    public function findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): ?NodeInterface
    {
        // TODO: Implement findNodeByNodeAggregateIdentifier() method.
    }

    public function countChildNodes(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeTypeConstraints $nodeTypeConstraints = null
    ): int {
        // TODO: Implement countChildNodes() method.
    }

    public function findParentNode(NodeAggregateIdentifier $childAggregateIdentifier): ?NodeInterface
    {
        // TODO: Implement findParentNode() method.
    }

    public function findNodeByPath(
        NodePath $path,
        NodeAggregateIdentifier $startingNodeAggregateIdentifier
    ): ?NodeInterface {
        // TODO: Implement findNodeByPath() method.
    }

    public function findChildNodeConnectedThroughEdgeName(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $edgeName
    ): ?NodeInterface {
        // TODO: Implement findChildNodeConnectedThroughEdgeName() method.
    }

    public function findSiblings(
        NodeAggregateIdentifier $sibling,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): array {
        // TODO: Implement findSiblings() method.
    }

    public function findSucceedingSiblings(
        NodeAggregateIdentifier $sibling,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): array {
        // TODO: Implement findSucceedingSiblings() method.
    }

    public function findPrecedingSiblings(
        NodeAggregateIdentifier $sibling,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): array {
        // TODO: Implement findPrecedingSiblings() method.
    }

    public function findNodePath(NodeAggregateIdentifier $nodeAggregateIdentifier): NodePath
    {
        // TODO: Implement findNodePath() method.
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        // TODO: Implement getContentStreamIdentifier() method.
    }

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        // TODO: Implement getDimensionSpacePoint() method.
    }

    public function findSubtrees(
        array $entryNodeAggregateIdentifiers,
        int $maximumLevels,
        NodeTypeConstraints $nodeTypeConstraints
    ): SubtreeInterface {
        // TODO: Implement findSubtrees() method.
    }

    public function findDescendants(
        array $entryNodeAggregateIdentifiers,
        NodeTypeConstraints $nodeTypeConstraints,
        ?SearchTerm $searchTerm
    ): array {
        // TODO: Implement findDescendants() method.
    }

    public function countNodes(): int
    {
        // TODO: Implement countNodes() method.
    }

    public function getInMemoryCache(): InMemoryCache
    {
        // TODO: Implement getInMemoryCache() method.
    }

    public function jsonSerialize()
    {
        // TODO: Implement jsonSerialize() method.
    }
}
