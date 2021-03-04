<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Api\Domain\Feature;

/*
 * This file is part of the Neos.ContentRepository.Api package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Api\Domain\NodeBasedReadModelInterface;
use Neos\ContentRepository\Api\Domain\NodeBasedReadModels;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraints;
use Neos\ContentRepository\Exception\NodeException;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;

/**
 * The feature interface declaring subgraph traversal
 */
interface SubgraphTraversalInterface
{
    /**
     * Retrieves and returns the parent node from the node's subgraph.
     * If no parent node is present, an {@link NodeException} is thrown.
     *
     * @throws NodeException If this node has no parent (i.e. is the root)
     */
    public function findParentNode(): NodeBasedReadModelInterface;

    /**
     * Retrieves and returns the node's path to its root node.
     */
    public function findNodePath(): NodePath;

    /**
     * Retrieves and returns a child node by name from the node's subgraph.
     *
     * @throws NodeException If no child node with the given $nodeName can be found
     */
    public function findNamedChildNode(NodeName $nodeName): NodeBasedReadModelInterface;

    /**
     * Retrieves and returns all direct child nodes of this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     */
    public function findChildNodes(NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): NodeBasedReadModels;

    /**
     * Returns the number of direct child nodes of this node from its subgraph.
     */
    public function countChildNodes(NodeTypeConstraints $nodeTypeConstraints = null): int;

    /**
     * Retrieves and returns all sibling nodes of this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     */
    public function findSiblingNodes(
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): NodeBasedReadModels;

    /**
     * Retrieves and returns all preceding sibling nodes of this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     */
    public function findPrecedingSiblingNodes(
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): NodeBasedReadModels;

    /**
     * Retrieves and returns all succeeding sibling nodes of this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     */
    public function findSucceedingSiblingNodes(
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): NodeBasedReadModels;

    /**
     * Retrieves and returns all nodes referenced by this node from its subgraph.
     */
    public function findReferencedNodes(): NodeBasedReadModels;

    /**
     * Retrieves and returns nodes referenced by this node by name from its subgraph.
     */
    public function findNamedReferencedNodes(PropertyName $edgeName): NodeBasedReadModels;

    /**
     * Retrieves and returns nodes referencing this node from its subgraph.
     */
    public function findReferencingNodes(): NodeBasedReadModels;

    /**
     * Retrieves and returns nodes referencing this node by name from its subgraph.
     */
    public function findNamedReferencingNodes(PropertyName $nodeName): NodeBasedReadModels;
}
