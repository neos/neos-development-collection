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
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

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
     * Find a single node by its aggregate id
     *
     * @return Node|null the node or NULL if no node with the specified id is accessible in this subgraph
     */
    public function findNodeById(NodeAggregateId $nodeAggregateId): ?Node;

    /**
     * Find direct child nodes of the specified parent node that match the given $filter
     */
    public function findChildNodes(NodeAggregateId $parentNodeAggregateId, Filter\FindChildNodesFilter $filter): Nodes;

    /**
     * Count direct child nodes of the specified parent node that match the given $filter
     * @see findChildNodes
     */
    public function countChildNodes(NodeAggregateId $parentNodeAggregateId, Filter\CountChildNodesFilter $filter): int;

    /**
     * Find the direct parent of a node specified by its aggregate id
     *
     * @return Node|null the node or NULL if the specified node is the root node or is inaccessible
     */
    public function findParentNode(NodeAggregateId $childNodeAggregateId): ?Node;

    /**
     * Find all nodes that are positioned _behind_ the specified sibling and match the specified $filter
     */
    public function findSucceedingSiblingNodes(NodeAggregateId $siblingNodeAggregateId, Filter\FindSucceedingSiblingNodesFilter $filter): Nodes;

    /**
     * Find all nodes that are positioned _before_ the specified sibling and match the specified $filter
     */
    public function findPrecedingSiblingNodes(NodeAggregateId $siblingNodeAggregateId, Filter\FindPrecedingSiblingNodesFilter $filter): Nodes;

    /**
     * Find a single child node by its name
     *
     * @return Node|null the node that is connected to its parent with the specified $edgeName, or NULL if no matching node exists or the parent node is not accessible
     */
    public function findChildNodeConnectedThroughEdgeName(NodeAggregateId $parentNodeAggregateId, NodeName $edgeName): ?Node;

    /**
     * Recursively find all nodes underneath the $entryNodeAggregateId that match the specified $filter and return them as a flat list
     *
     * Note: This is basically a set-based view of descendant nodes; so the ordering should not be relied upon
     */
    public function findDescendantNodes(NodeAggregateId $entryNodeAggregateId, Filter\FindDescendantNodesFilter $filter): Nodes;

    /**
     * Count all nodes underneath the $entryNodeAggregateId that match the specified $filter
     * @see findDescendantNodes
     */
    public function countDescendantNodes(NodeAggregateId $entryNodeAggregateId, Filter\CountDescendantNodesFilter $filter): int;

    /**
     * Recursively find all nodes underneath the $entryNodeAggregateId that match the specified $filter and return them as a tree
     *
     * Note: This returns a fragment of the existing tree structure. The structure is kept intact but nodes might be missing depending on the specified filter
     *
     * @return Subtree|null the recursive tree of all matching nodes, or NULL if the entry node is not accessible
     */
    public function findSubtree(NodeAggregateId $entryNodeAggregateId, Filter\FindSubtreeFilter $filter): ?Subtree;

    /**
     * Find all "outgoing" references of a given node that match the specified $filter
     *
     * A reference is a node property of type "reference" or "references"
     * Because each reference has a name and can contain properties itself, this method does not return the target nodes
     * directly, but actual {@see \Neos\ContentRepository\Core\Projection\ContentGraph\Reference} instances.
     * The corresponding nodes can be retrieved via {@see References::getNodes()}
     */
    public function findReferences(NodeAggregateId $nodeAggregateId, Filter\FindReferencesFilter $filter): References;

    /**
     * Count all "outgoing" references of a given node that match the specified $filter
     * @see findReferences
     */
    public function countReferences(NodeAggregateId $nodeAggregateId, Filter\CountReferencesFilter $filter): int;

    /**
     * Find all "incoming" references of a given node that match the specified $filter
     * If nodes "A" and "B" both have a reference to "C", the node "C" has two incoming references.
     *
     * @see findReferences
     */
    public function findBackReferences(NodeAggregateId $nodeAggregateId, Filter\FindBackReferencesFilter $filter): References;

    /**
     * Count all "incoming" references of a given node that match the specified $filter
     * @see findBackReferences
     */
    public function countBackReferences(NodeAggregateId $nodeAggregateId, Filter\CountBackReferencesFilter $filter): int;

    /**
     * Find a single node underneath $startingNodeAggregateId that matches the specified $path
     *
     * NOTE: This operation is most likely to be deprecated since the concept of node paths is not really used in the core, and it has some logical issues
     * @return Node|null the node that matches the given $path, or NULL if no node on that path is accessible
     */
    public function findNodeByPath(NodePath $path, NodeAggregateId $startingNodeAggregateId): ?Node;

    /**
     * Determine the absolute path of a node
     *
     * NOTE: This operation is most likely to be deprecated since the concept of node paths is not really used in the core, and it has some logical issues
     * @throws \InvalidArgumentException if the node path could not be retrieved because it is inaccessible or contains no valid path. The latter can happen if any node in the hierarchy has no name
     */
    public function retrieveNodePath(NodeAggregateId $nodeAggregateId): NodePath;

    /**
     * Count all nodes in this subgraph, including inaccessible ones!
     *
     * @return int
     * @internal this method might change without further notice.
     */
    public function countNodes(): int;
}
