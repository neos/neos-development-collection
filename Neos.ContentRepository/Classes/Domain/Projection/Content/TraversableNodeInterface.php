<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Domain\Projection\Content;

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
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraints;
use Neos\ContentRepository\Exception\NodeException;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;

/**
 * This is a NEW interface, introduced in Neos 4.3; and it will become the main interface
 * with Neos 5.0 to the CR.
 *
 * The main convenience Event-Sourced NodeInterface used for READING; containing
 * data accessors and traversal methods.
 *
 * All methods which are called `get*()` contain only information local to a node,
 * so they can be accessed really quickly without any external lookup.
 *
 * All methods which are called `find*()` may involve some database querying to
 * fetch their information.
 *
 * The TraversableNodeInterface is *immutable*, meaning its contents never change after creation.
 * It is *only used for reading*.
 *
 * Starting with version 5.0 (when backed by the Event Sourced CR), it is
 * *completely detached from storage*; so it will not auto-update after a property changed in
 * storage.
 */
interface TraversableNodeInterface extends NodeInterface
{
    /**
     * Returns the DimensionSpacePoint the node was *requested in*, i.e. one of the DimensionSpacePoints
     * this node is visible in. If you need the DimensionSpacePoint where the node is actually at home,
     * see getOriginDimensionSpacePoint()
     *
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint;

    /**
     * Retrieves and returns the parent node from the node's subgraph.
     * If no parent node is present, an {@link NodeException} is thrown.
     *
     * @return TraversableNodeInterface the parent node, never <code>null</code>
     * @throws NodeException If this node has no parent (i.e. is the root)
     */
    public function findParentNode(): TraversableNodeInterface;

    /**
     * Retrieves and returns the node's path to its root node.
     *
     * @return NodePath
     */
    public function findNodePath(): NodePath;

    /**
     * Retrieves and returns a child node by name from the node's subgraph.
     *
     * @param NodeName $nodeName The name
     * @return TraversableNodeInterface
     * @throws NodeException If no child node with the given $nodeName can be found
     */
    public function findNamedChildNode(NodeName $nodeName): TraversableNodeInterface;

    /**
     * Retrieves and returns all direct child nodes of this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     *
     * @param NodeTypeConstraints $nodeTypeConstraints If specified, only nodes with that node type are considered
     * @param int $limit An optional limit for the number of nodes to find. Added or removed nodes can still change the number nodes!
     * @param int $offset An optional offset for the query
     * @return TraversableNodes Traversable nodes that matched the given constraints
     * @api
     */
    public function findChildNodes(NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): TraversableNodes;

    /**
     * Returns the number of direct child nodes of this node from its subgraph.
     *
     * @param NodeTypeConstraints|null $nodeTypeConstraints If specified, only nodes with that node type are considered
     * @return int
     */
    public function countChildNodes(NodeTypeConstraints $nodeTypeConstraints = null): int;

    /**
     * Retrieves and returns all nodes referenced by this node from its subgraph.
     *
     * @return TraversableNodes
     */
    public function findReferencedNodes(): TraversableNodes;

    /**
     * Retrieves and returns nodes referenced by this node by name from its subgraph.
     *
     * @param PropertyName $edgeName
     * @return TraversableNodes
     */
    public function findNamedReferencedNodes(PropertyName $edgeName): TraversableNodes;

    /**
     * Retrieves and returns nodes referencing this node from its subgraph.
     *
     * @return TraversableNodes
     */
    public function findReferencingNodes(): TraversableNodes;

    /**
     * Retrieves and returns nodes referencing this node by name from its subgraph.
     *
     * @param PropertyName $nodeName
     * @return TraversableNodes
     */
    public function findNamedReferencingNodes(PropertyName $nodeName): TraversableNodes;

    /**
     * Compare whether two traversable nodes are equal
     *
     * @param TraversableNodeInterface $other
     * @return bool
     */
    public function equals(TraversableNodeInterface $other): bool;
}
