<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Projection\Content;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraints;
use Neos\ContentRepository\Feature\SubtreeInterface;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifiers;
use Neos\ContentRepository\SharedModel\Node\PropertyName;

/**
 * This is the most important read model of a content repository.
 *
 * It is a "view" to the content graph, only showing a single dimension
 * (e.g. "language=de,country=ch") - so this means this is effectively
 * **a tree of nodes**.
 *
 * ## Accessing the Content Subgraph
 *
 * From the central Content Repository instance, you can fetch the singleton
 * {@see ContentGraphInterface}. There, you can call
 * {@see ContentGraphInterface::getSubgraphByIdentifier()} and pass in
 * the {@see ContentStreamIdentifier}, {@see DimensionSpacePoint} and
 * {@see VisibilityConstraints} you want to have.
 *
 *
 * ## Why is this called "Subgraph" and not Tree?
 *
 * This is because a tree can have only a single root node, but the ContentSubgraph
 * supports multiple root nodes. So the totally correct term would be a "Forest",
 * but this is unknown terminology outside academia. This is why we go for "Subgraph"
 * to show that this is a part of the Content Graph.
 *
 * @api
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

    public function findReferencedNodes(
        NodeAggregateIdentifier $nodeAggregateAggregateIdentifier,
        PropertyName $name = null
    ): References;

    public function findReferencingNodes(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        PropertyName $name = null
    ): References;

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
