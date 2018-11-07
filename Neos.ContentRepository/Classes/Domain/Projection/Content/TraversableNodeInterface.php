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

/**
 * A convenience wrapper.
 *
 * Immutable. Read-only. With traversal operations.
 *
 * !! Reference resolving happens HERE!
 */
interface TraversableNodeInterface extends NodeInterface
{
    /**
     * Retrieves and returns the parent node from the node's subgraph.
     * Returns null if this is a root node.
     *
     * @return TraversableNodeInterface|null
     */
    public function findParentNode(): ?TraversableNodeInterface;

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
     * @return TraversableNodeInterface|null
     */
    public function findNamedChildNode(NodeName $nodeName): ?TraversableNodeInterface;

    /**
     * Retrieves and returns all direct child nodes of this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     *
     * @param NodeTypeConstraints $nodeTypeConstraints If specified, only nodes with that node type are considered
     * @param int $limit An optional limit for the number of nodes to find. Added or removed nodes can still change the number nodes!
     * @param int $offset An optional offset for the query
     * @return TraversableNodeInterface[]|array<TraversableNodeInterface> An array of nodes or an empty array if no child nodes matched
     * @api
     */
    public function findChildNodes(NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null);

    /**
     * Retrieves and returns all sibling nodes of this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     *
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array<TraversableNodeInterface>|TraversableNodeInterface[]
     */
    public function findSiblingNodes(NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array;

    /**
     * Retrieves and returns all preceding sibling nodes of this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     *
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function findPrecedingSiblingNodes(NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array;

    /**
     * Retrieves and returns all succeeding sibling nodes of this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     *
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function findSucceedingSiblingNodes(NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array;

    /**
     * Retrieves and returns all nodes referenced by this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     *
     * @return array<TraversableNodeInterface>|TraversableNodeInterface[]
     */
    public function findReferencedNodes(): array;

    /**
     * Retrieves and returns nodes referenced by this node by name from its subgraph.
     *
     * @param NodeName $nodeName
     * @return array<TraversableNodeInterface>|TraversableNodeInterface[]
     */
    public function findNamedReferencedNodes(NodeName $nodeName): array;

    /**
     * Retrieves and returns nodes referencing this node from its subgraph.
     *
     * @return array<TraversableNodeInterface>|TraversableNodeInterface[]
     */
    public function findReferencingNodes(): array;

    /**
     * Retrieves and returns nodes referencing this node by name from its subgraph.
     *
     * @param NodeName $nodeName
     * @return array<TraversableNodeInterface>|TraversableNodeInterface[]
     */
    public function findNamedReferencingNodes(NodeName $nodeName): array;
}
