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

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

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
 * {@see ContentGraphInterface::getSubgraph()} and pass in
 * the {@see ContentStreamId}, {@see DimensionSpacePoint} and
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
     * @param NodeAggregateId $parentNodeAggregateId
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return Nodes
     */
    public function findChildNodes(
        NodeAggregateId $parentNodeAggregateId,
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes;

    public function findReferencedNodes(
        NodeAggregateId $nodeAggregateId,
        PropertyName $name = null
    ): References;

    public function findReferencingNodes(
        NodeAggregateId $nodeAggregateId,
        PropertyName $name = null
    ): References;

    /**
     * TODO: RENAME: findById? or findByNodeAggregateId?
     * @param NodeAggregateId $nodeAggregateId
     * @return Node|null
     */
    public function findNodeByNodeAggregateId(NodeAggregateId $nodeAggregateId): ?Node;

    /**
     * @param NodeAggregateId $parentNodeAggregateId
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @return int
     */
    public function countChildNodes(
        NodeAggregateId $parentNodeAggregateId,
        NodeTypeConstraints $nodeTypeConstraints = null
    ): int;

    /**
     * @param NodeAggregateId $childNodeAggregateId
     * @return Node|null
     */
    public function findParentNode(NodeAggregateId $childNodeAggregateId): ?Node;

    /**
     * @param NodePath $path
     * @param NodeAggregateId $startingNodeAggregateId
     * @return Node|null
     */
    public function findNodeByPath(
        NodePath $path,
        NodeAggregateId $startingNodeAggregateId
    ): ?Node;

    /**
     * @param NodeAggregateId $parentNodeAggregateId
     * @param NodeName $edgeName
     * @return Node|null
     */
    public function findChildNodeConnectedThroughEdgeName(
        NodeAggregateId $parentNodeAggregateId,
        NodeName $edgeName
    ): ?Node;

    /**
     * @param NodeAggregateId $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return Nodes
     */
    public function findSiblings(
        NodeAggregateId $sibling,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes;

    /**
     * @param NodeAggregateId $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return Nodes
     */
    public function findSucceedingSiblings(
        NodeAggregateId $sibling,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes;

    /**
     * @param NodeAggregateId $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return Nodes
     */
    public function findPrecedingSiblings(
        NodeAggregateId $sibling,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes;

    /**
     * @param NodeAggregateId $nodeAggregateId
     * @return NodePath
     */
    public function findNodePath(NodeAggregateId $nodeAggregateId): NodePath;

    /**
     * @return ContentStreamId
     */
    public function getContentStreamId(): ContentStreamId;

    /**
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint;

    public function findSubtrees(
        NodeAggregateIds $entryNodeAggregateIds,
        int $maximumLevels,
        NodeTypeConstraints $nodeTypeConstraints
    ): Subtree;

    /**
     * Recursively find all nodes underneath the $entryNodeAggregateIds,
     * which match the node type constraints specified by NodeTypeConstraints.
     *
     * If a Search Term is specified, the properties are searched for this search term.
     *
     * @param array<int,NodeAggregateId> $entryNodeAggregateIds
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @param SearchTerm|null $searchTerm
     * @return Nodes
     */
    public function findDescendants(
        array $entryNodeAggregateIds,
        NodeTypeConstraints $nodeTypeConstraints,
        ?SearchTerm $searchTerm
    ): Nodes;

    public function countNodes(): int;
}
