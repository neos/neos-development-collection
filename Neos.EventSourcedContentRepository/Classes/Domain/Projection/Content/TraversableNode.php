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


    public function getSubgraph(): ContentSubgraphInterface
    {
        return $this->subgraph;
    }

    public function getContextParameters(): ContextParameters
    {
        return $this->contextParameters;
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

    public function findChildNodes(NodeTypeConstraints $nodeTypeConstraints = null, $limit = null, $offset = null)
    {
        $childNodes = $this->subgraph->findChildNodes($this->node->getNodeIdentifier(), $nodeTypeConstraints, $limit, $offset);

        $traversableChildNodes = [];
        foreach ($childNodes as $node) {
            $traversableChildNodes[] = new TraversableNode($node, $this->subgraph, $this->contextParameters);
        }
        return $traversableChildNodes;
    }

    public function findNodePath(): NodePath
    {
        return $this->subgraph->findNodePath($this->node->getNodeIdentifier());
    }

    /**
     * @return TraversableNode[]
     */
    public function findReferencingNodes(): array {
        $nodes = $this->subgraph->findReferencingNodes($this->node->getNodeIdentifier());

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
