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

use Neos\EventSourcedContentRepository\Domain;
use Neos\EventSourcedContentRepository\Domain\Model\NodeType;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode\NodeInterfaceProxy;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodePath;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeTypeConstraints;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\Cache\CacheAwareInterface;
use Neos\Eel\ProtectedContextAwareInterface;

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
     * @var Domain\Context\Parameters\ContextParameters
     */
    protected $contextParameters;

    public function __construct(NodeInterface $node, ContentSubgraphInterface $subgraph, Domain\Context\Parameters\ContextParameters $contextParameters)
    {
        $this->node = $node;
        $this->subgraph = $subgraph;
        $this->contextParameters = $contextParameters;
    }


    public function getSubgraph(): ContentSubgraphInterface
    {
        return $this->subgraph;
    }

    public function getContextParameters(): Domain\Context\Parameters\ContextParameters
    {
        return $this->contextParameters;
    }

    // TODO: rename to findParent() because it is a DB operation
    public function getParent(): ?TraversableNodeInterface
    {
        $node = $this->subgraph->findParentNode($this->node->getNodeIdentifier());
        return $node ? new TraversableNode($node, $this->subgraph, $this->contextParameters) : null;
    }

    public function findNamedChildNode(NodeName $nodeName): ?TraversableNodeInterface
    {
        $node = $this->subgraph->findChildNodeConnectedThroughEdgeName($this->node->getNodeIdentifier(), $nodeName);
        return $node ? new TraversableNode($node, $this->subgraph, $this->contextParameters) : null;
    }

    // TODO: rename to findChildNodes() because it is a DB operation
    public function getChildNodes(NodeTypeConstraints $nodeTypeConstraints = null, $limit = null, $offset = null)
    {
        $childNodes = $this->subgraph->findChildNodes($this->node->getNodeIdentifier(), $nodeTypeConstraints, $limit, $offset);

        $traversableChildNodes = [];
        foreach ($childNodes as $node) {
            $traversableChildNodes[] = new TraversableNode($node, $this->subgraph, $this->contextParameters);
        }
        return $traversableChildNodes;
    }

    // TODO: rename to findNodePath() because it is a DB operation
    public function getNodePath(): NodePath
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
