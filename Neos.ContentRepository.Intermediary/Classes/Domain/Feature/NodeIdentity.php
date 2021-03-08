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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;

/**
 * The feature trait implementing the node identity interface based on a node and a subgraph
 */
trait NodeIdentity
{
    private NodeInterface $node;

    private ContentSubgraphInterface $subgraph;

    private WorkspaceName $workspaceName;

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->node->getContentStreamIdentifier();
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->node->getNodeAggregateIdentifier();
    }

    /**
     * Returns the DimensionSpacePoint the node was *requested in*, i.e. one of the DimensionSpacePoints
     * this node is visible in. If you need the DimensionSpacePoint where the node is actually at home,
     * see getOriginDimensionSpacePoint()
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->subgraph->getDimensionSpacePoint();
    }

    /**
     * returns the DimensionSpacePoint the node is at home in. Usually needed to address a Node in a NodeAggregate
     * in order to update it.
     */
    public function getOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->node->getOriginDimensionSpacePoint();
    }

    public function getAddress(): NodeAddress
    {
        return new NodeAddress(
            $this->subgraph->getContentStreamIdentifier(),
            $this->subgraph->getDimensionSpacePoint(),
            $this->node->getNodeAggregateIdentifier(),
            $this->workspaceName
        );
    }

    /**
     * Compare whether two nodes are equal
     */
    public function equals(NodeIdentityInterface $other): bool
    {
        return $this->getContentStreamIdentifier()->equals($other->getContentStreamIdentifier())
            && $this->getDimensionSpacePoint()->equals($other->getDimensionSpacePoint())
            && $this->getNodeAggregateIdentifier()->equals($other->getNodeAggregateIdentifier());
    }

    public function getCacheEntryIdentifier(): string
    {
        return $this->node->getCacheEntryIdentifier();
    }
}
