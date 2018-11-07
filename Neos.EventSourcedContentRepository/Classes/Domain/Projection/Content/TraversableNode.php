<?php

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

use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodePath;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeConstraints;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\ContextParameters;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode\NodeInterfaceProxy;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;

/**
 * See {@see TraversableNodeInterface} for explanation.
 */
final class TraversableNode implements TraversableNodeInterface, ProtectedContextAwareInterface
{
    use NodeInterfaceProxy;

    /**
     * @var ContentSubgraphInterface
     */
    protected $subgraph;

    /**
     * @var ContextParameters
     */
    protected $contextParameters;

    public function __construct(NodeInterface $node, ContentSubgraphInterface $subgraph, ContextParameters $contextParameters)
    {
        $this->node = $node;
        $this->subgraph = $subgraph;
        $this->contextParameters = $contextParameters;
    }

    public function findParentNode(): ?TraversableNodeInterface
    {
        $node = $this->subgraph->findParentNode($this->node->getNodeIdentifier());
        return $node ? new TraversableNode($node, $this->subgraph, $this->contextParameters) : null;
    }

    public function findNamedChildNode(NodeName $nodeName): ?TraversableNodeInterface
    {
        $node = $this->subgraph->findChildNodeConnectedThroughEdgeName($this->node->getNodeIdentifier(), $nodeName);
        return $node ? new TraversableNode($node, $this->subgraph, $this->contextParameters) : null;
    }

    /**
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array|TraversableNodeInterface[]
     */
    public function findChildNodes(NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array
    {
        $childNodes = $this->subgraph->findChildNodes($this->node->getNodeIdentifier(), $nodeTypeConstraints, $limit, $offset);

        $traversableChildNodes = [];
        foreach ($childNodes as $node) {
            $traversableChildNodes[] = new TraversableNode($node, $this->subgraph, $this->contextParameters);
        }
        return $traversableChildNodes;
    }

    public function countChildNodes(NodeTypeConstraints $nodeTypeConstraints = null): int
    {
        return $this->subgraph->countChildNodes($this->node->getNodeIdentifier(), $nodeTypeConstraints);
    }

    public function findNodePath(): NodePath
    {
        return $this->subgraph->findNodePath($this->node->getNodeIdentifier());
    }

    /**
     * @return array|TraversableNode[]
     */
    public function findReferencingNodes(): array
    {
        $nodes = $this->subgraph->findReferencingNodes($this->node->getNodeIdentifier());

        $traversableNodes = [];
        foreach ($nodes as $node) {
            $traversableNodes[] = new TraversableNode($node, $this->subgraph, $this->contextParameters);
        }
        return $traversableNodes;
    }

    /**
     * Retrieves and returns nodes referencing this node by name from its subgraph.
     *
     * @param PropertyName $edgeName
     * @return array<TraversableNodeInterface>|TraversableNodeInterface[]
     */
    public function findNamedReferencingNodes(PropertyName $edgeName): array
    {
        $nodes = $this->subgraph->findReferencingNodes($this->node->getNodeIdentifier(), $edgeName);

        $traversableNodes = [];
        foreach ($nodes as $node) {
            $traversableNodes[] = new TraversableNode($node, $this->subgraph, $this->contextParameters);
        }
        return $traversableNodes;
    }

    /**
     * Retrieves and returns all sibling nodes of this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     *
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array<TraversableNodeInterface>|TraversableNodeInterface[]
     */
    public function findSiblingNodes(
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): array {
        $nodes = $this->subgraph->findSiblings($this->node->getNodeAggregateIdentifier(), $nodeTypeConstraints, $limit, $offset);

        $traversableNodes = [];
        foreach ($nodes as $node) {
            $traversableNodes[] = new TraversableNode($node, $this->subgraph, $this->contextParameters);
        }
        return $traversableNodes;
    }

    /**
     * Retrieves and returns all preceding sibling nodes of this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     *
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function findPrecedingSiblingNodes(
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): array {
        $nodes = $this->subgraph->findPrecedingSiblings($this->node->getNodeAggregateIdentifier());

        $traversableNodes = [];
        foreach ($nodes as $node) {
            $traversableNodes[] = new TraversableNode($node, $this->subgraph, $this->contextParameters);
        }
        return $traversableNodes;
    }

    /**
     * Retrieves and returns all succeeding sibling nodes of this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     *
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function findSucceedingSiblingNodes(
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): array {
        $nodes = $this->subgraph->findSucceedingSiblings($this->node->getNodeAggregateIdentifier());

        $traversableNodes = [];
        foreach ($nodes as $node) {
            $traversableNodes[] = new TraversableNode($node, $this->subgraph, $this->contextParameters);
        }
        return $traversableNodes;
    }

    /**
     * Retrieves and returns all nodes referenced by this node from its subgraph.
     * If node type constraints are specified, only nodes of that type are returned.
     *
     * @return array<TraversableNodeInterface>|TraversableNodeInterface[]
     */
    public function findReferencedNodes(): array
    {
        $nodes = $this->subgraph->findReferencedNodes($this->node->getNodeIdentifier());

        $traversableNodes = [];
        foreach ($nodes as $node) {
            $traversableNodes[] = new TraversableNode($node, $this->subgraph, $this->contextParameters);
        }
        return $traversableNodes;
    }

    /**
     * Retrieves and returns nodes referenced by this node by name from its subgraph.
     *
     * @param PropertyName $edgeName
     * @return array<TraversableNodeInterface>|TraversableNodeInterface[]
     */
    public function findNamedReferencedNodes(PropertyName $edgeName): array
    {
        $nodes = $this->subgraph->findReferencedNodes($this->node->getNodeIdentifier(), $edgeName);

        $traversableNodes = [];
        foreach ($nodes as $node) {
            $traversableNodes[] = new TraversableNode($node, $this->subgraph, $this->contextParameters);
        }
        return $traversableNodes;
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
