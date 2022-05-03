<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraints;
use Neos\EventSourcedContentRepository\Domain\Context\ContentSubgraph\SubtreeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifiers;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;

/**
 * The interface to be implemented by content subgraphs
 */
interface ContentSubgraphInterface extends \JsonSerializable
{
    /**
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return Nodes
     */
    public function findChildNodes(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes;

    /**
     * @param NodeAggregateIdentifier $nodeAggregateAggregateIdentifier
     * @param PropertyName|null $name
     * @return Nodes
     */
    public function findReferencedNodes(
        NodeAggregateIdentifier $nodeAggregateAggregateIdentifier,
        PropertyName $name = null
    ): Nodes;

    public function findReferencingNodes(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        PropertyName $name = null
    ): Nodes;

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): ?NodeInterface;

    /**
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @return int
     */
    public function countChildNodes(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeTypeConstraints $nodeTypeConstraints = null
    ): int;

    /**
     * @param NodeAggregateIdentifier $childNodeAggregateIdentifier
     * @return NodeInterface|null
     */
    public function findParentNode(NodeAggregateIdentifier $childNodeAggregateIdentifier): ?NodeInterface;

    /**
     * @param NodePath $path
     * @param NodeAggregateIdentifier $startingNodeAggregateIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByPath(
        NodePath $path,
        NodeAggregateIdentifier $startingNodeAggregateIdentifier
    ): ?NodeInterface;

    /**
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeName $edgeName
     * @return NodeInterface|null
     */
    public function findChildNodeConnectedThroughEdgeName(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $edgeName
    ): ?NodeInterface;

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return Nodes
     */
    public function findSiblings(
        NodeAggregateIdentifier $sibling,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes;

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return Nodes
     */
    public function findSucceedingSiblings(
        NodeAggregateIdentifier $sibling,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes;

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return Nodes
     */
    public function findPrecedingSiblings(
        NodeAggregateIdentifier $sibling,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes;

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return NodePath
     */
    public function findNodePath(NodeAggregateIdentifier $nodeAggregateIdentifier): NodePath;

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier;

    /**
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint;

    public function findSubtrees(
        NodeAggregateIdentifiers $entryNodeAggregateIdentifiers,
        int $maximumLevels,
        NodeTypeConstraints $nodeTypeConstraints
    ): SubtreeInterface;

    /**
     * Recursively find all nodes underneath the $entryNodeAggregateIdentifiers,
     * which match the node type constraints specified by NodeTypeConstraints.
     *
     * If a Search Term is specified, the properties are searched for this search term.
     *
     * @param array<int,NodeAggregateIdentifier> $entryNodeAggregateIdentifiers
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @param SearchTerm|null $searchTerm
     * @return Nodes
     */
    public function findDescendants(
        array $entryNodeAggregateIdentifiers,
        NodeTypeConstraints $nodeTypeConstraints,
        ?SearchTerm $searchTerm
    ): Nodes;

    public function countNodes(): int;

    public function getInMemoryCache(): InMemoryCache;
}
