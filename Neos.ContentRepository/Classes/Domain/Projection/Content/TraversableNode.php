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

use Neos\ContentRepository\Domain;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNode\NodeInterfaceProxy;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodePath;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeConstraints;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\Cache\CacheAwareInterface;

/**
 * See {@see TraversableNodeInterface} for explanation.
 */
final class TraversableNode implements TraversableNodeInterface
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

    public function getChildNodes(NodeTypeConstraints $nodeTypeConstraints = null, $limit = null, $offset = null)
    {
        $childNodes = $this->subgraph->findChildNodes($this->node->getNodeIdentifier(), $nodeTypeConstraints, $limit, $offset);

        $traversableChildNodes = [];
        foreach ($childNodes as $node) {
            $traversableChildNodes[] = new TraversableNode($node, $this->subgraph, $this->contextParameters);
        }
        return $traversableChildNodes;
    }

    public function getNodePath(): NodePath
    {
        return $this->subgraph->findNodePath($this->node->getNodeIdentifier());
    }
}
