<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Domain\Feature;

/*
 * This file is part of the Neos.ContentRepository.Api package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;
use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModels;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Exception\NodeException;
use Neos\ContentRepository\Intermediary\Domain\ReadModelFactory;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;

/**
 * The feature trait implementing subgraph traversal based on a node, a subgraph and using the factory
 */
trait SubgraphTraversal
{
    private NodeInterface $node;

    private ContentSubgraphInterface $subgraph;

    private ReadModelFactory $readModelFactory;

    /**
     * Retrieves and returns the parent node from the node's subgraph.
     * If no parent node is present, an {@link NodeException} is thrown.
     *
     * @throws NodeException If this node has no parent (i.e. is the root)
     */
    public function findParentNode(): NodeBasedReadModelInterface
    {
        $parentNode = $this->subgraph->findParentNode($this->node->getNodeAggregateIdentifier());
        if (!$parentNode) {
            throw new NodeException('This node has no parent', 1542982973);
        }

        return $this->readModelFactory->createReadModel($parentNode, $this->subgraph);
    }

    /**
     * Retrieves and returns the node's path to its root node.
     */
    public function findNodePath(): NodePath
    {
        return $this->subgraph->findNodePath($this->node->getNodeAggregateIdentifier());
    }

    /**
     * Retrieves and returns a child node by name from the node's subgraph.
     *
     * @throws NodeException If no child node with the given $nodeName can be found
     */
    public function findNamedChildNode(NodeName $nodeName): NodeBasedReadModelInterface
    {
        $namedChildNode = $this->subgraph->findChildNodeConnectedThroughEdgeName($this->node->getNodeAggregateIdentifier(), $nodeName);
        if (!$namedChildNode) {
            throw new NodeException(sprintf('Child node with name "%s" does not exist', $nodeName), 1542982917);
        }

        return $this->readModelFactory->createReadModel($namedChildNode, $this->subgraph);
    }

    /**
     * Retrieves and returns all direct child nodes of this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     */
    public function findChildNodes(NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): NodeBasedReadModels
    {
        $childNodes = $this->subgraph->findChildNodes($this->node->getNodeAggregateIdentifier(), $nodeTypeConstraints, $limit, $offset);

        return $this->readModelFactory->createReadModels($childNodes, $this->subgraph);
    }

    /**
     * Returns the number of direct child nodes of this node from its subgraph.
     */
    public function countChildNodes(NodeTypeConstraints $nodeTypeConstraints = null): int
    {
        return $this->subgraph->countChildNodes($this->node->getNodeAggregateIdentifier(), $nodeTypeConstraints);
    }

    /**
     * Retrieves and returns all sibling nodes of this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     */
    public function findSiblingNodes(
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): NodeBasedReadModels {
        $siblingNodes = $this->subgraph->findSiblings($this->node->getNodeAggregateIdentifier(), $nodeTypeConstraints, $limit, $offset);

        return $this->readModelFactory->createReadModels($siblingNodes, $this->subgraph);
    }

    /**
     * Retrieves and returns all preceding sibling nodes of this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     */
    public function findPrecedingSiblingNodes(
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): NodeBasedReadModels {
        $siblingNodes = $this->subgraph->findPrecedingSiblings($this->node->getNodeAggregateIdentifier(), $nodeTypeConstraints, $limit, $offset);

        return $this->readModelFactory->createReadModels($siblingNodes, $this->subgraph);
    }

    /**
     * Retrieves and returns all succeeding sibling nodes of this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     */
    public function findSucceedingSiblingNodes(
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): NodeBasedReadModels {
        $siblingNodes = $this->subgraph->findSucceedingSiblings($this->node->getNodeAggregateIdentifier(), $nodeTypeConstraints, $limit, $offset);

        return $this->readModelFactory->createReadModels($siblingNodes, $this->subgraph);
    }

    /**
     * Retrieves and returns all nodes referenced by this node from its subgraph.
     */
    public function findReferencedNodes(): NodeBasedReadModels
    {
        $referencedNodes = $this->subgraph->findReferencedNodes($this->node->getNodeAggregateIdentifier());

        return $this->readModelFactory->createReadModels($referencedNodes, $this->subgraph);
    }

    /**
     * Retrieves and returns nodes referenced by this node by name from its subgraph.
     */
    public function findNamedReferencedNodes(PropertyName $edgeName): NodeBasedReadModels
    {
        $referencedNodes = $this->subgraph->findReferencedNodes($this->node->getNodeAggregateIdentifier(), $edgeName);

        return $this->readModelFactory->createReadModels($referencedNodes, $this->subgraph);
    }

    /**
     * Retrieves and returns nodes referencing this node from its subgraph.
     */
    public function findReferencingNodes(): NodeBasedReadModels
    {
        $referencingNodes = $this->subgraph->findReferencingNodes($this->node->getNodeAggregateIdentifier());

        return $this->readModelFactory->createReadModels($referencingNodes, $this->subgraph);
    }

    /**
     * Retrieves and returns nodes referencing this node by name from its subgraph.
     */
    public function findNamedReferencingNodes(PropertyName $edgeName): NodeBasedReadModels
    {
        $referencingNodes = $this->subgraph->findReferencingNodes($this->node->getNodeAggregateIdentifier(), $edgeName);

        return $this->readModelFactory->createReadModels($referencingNodes, $this->subgraph);
    }
}
