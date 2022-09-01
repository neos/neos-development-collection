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
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantsFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingsFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingsFilter;
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
     * @param FindChildNodesFilter $filter
     * @return Nodes
     */
    public function findChildNodes(
        NodeAggregateId $parentNodeAggregateId,
        Filter\FindChildNodesFilter $filter
    ): Nodes;

    public function findReferencedNodes(
        NodeAggregateId $nodeAggregateId,
        Filter\FindReferencedNodesFilter $filter
    ): References;

    public function findReferencingNodes(
        NodeAggregateId $nodeAggregateId,
        Filter\FindReferencingNodesFilter $filter
    ): References;

    /**
     * @param NodeAggregateId $nodeAggregateId
     * @return Node|null
     */
    public function findNodeById(NodeAggregateId $nodeAggregateId): ?Node;

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
     * @param FindSucceedingSiblingsFilter $filter
     * @return Nodes
     */
    public function findSucceedingSiblings(
        NodeAggregateId $sibling,
        Filter\FindSucceedingSiblingsFilter $filter
    ): Nodes;

    /**
     * @param NodeAggregateId $sibling
     * @param FindPrecedingSiblingsFilter $filter
     * @return Nodes
     */
    public function findPrecedingSiblings(
        NodeAggregateId $sibling,
        Filter\FindPrecedingSiblingsFilter $filter,
    ): Nodes;

    /**
     * @param NodeAggregateId $nodeAggregateId
     * @return NodePath
     */
    public function findNodePath(NodeAggregateId $nodeAggregateId): NodePath;

    public function findSubtrees(
        NodeAggregateIds $entryNodeAggregateIds,
        Filter\FindSubtreesFilter $filter
    ): Subtrees;

    /**
     * Recursively find all nodes underneath the $entryNodeAggregateIds,
     * which match the node type constraints specified by NodeTypeConstraints.
     *
     * If a Search Term is specified, the properties are searched for this search term.
     *
     * @param NodeAggregateIds $entryNodeAggregateIds
     * @param FindDescendantsFilter $filter
     * @return Nodes
     */
    public function findDescendants(
        NodeAggregateIds $entryNodeAggregateIds,
        Filter\FindDescendantsFilter $filter
    ): Nodes;

    /**
     * @return int
     * @internal this method might change without further notice.
     */
    public function countNodes(): int;
}
