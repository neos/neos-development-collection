<?php
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

use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodePath;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeConstraints;
use Neos\ContentRepository\Exception\NodeException;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;

/**
 * A convenience wrapper.
 *
 * Immutable. Read-only. With traversal operations.
 *
 * !! Reference resolving happens HERE!
 */
interface TraversableNodeInterface extends NodeInterface
{
    public function getSubgraph(): ContentSubgraphInterface;

    /**
     * Retrieves and returns the parent node from the node's subgraph.
     * Returns null if this is a root node.
     *
     * @return TraversableNodeInterface
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
     * If node type constraints are specified, only nodes of that type are returned.
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
}
