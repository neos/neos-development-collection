<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Helper;

use Neos\Cache\CacheAwareInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;

/**
 * The node discriminator value object
 *
 * Represents the identity of a specific node in the content graph and is thus composed of
 * * the content stream the node exists in
 * * the node's aggregate's external id
 * * the dimension space point the node originates in within its aggregate
 */
final class NodeDiscriminator implements \JsonSerializable
{
    private ContentStreamId $contentStreamId;

    private NodeAggregateId $nodeAggregateId;

    private OriginDimensionSpacePoint $originDimensionSpacePoint;

    private function __construct(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ) {
        $this->contentStreamId = $contentStreamId;
        $this->nodeAggregateId = $nodeAggregateId;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
    }

    public static function fromShorthand(string $shorthand): self
    {
        list($contentStreamId, $nodeAggregateId, $originDimensionSpacePoint) = explode(';', $shorthand);

        return new self(
            ContentStreamId::fromString($contentStreamId),
            NodeAggregateId::fromString($nodeAggregateId),
            OriginDimensionSpacePoint::fromJsonString($originDimensionSpacePoint)
        );
    }

    public static function fromNode(Node $node): self
    {
        return new NodeDiscriminator(
            $node->subgraphIdentity->contentStreamId,
            $node->nodeAggregateId,
            $node->originDimensionSpacePoint
        );
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }

    public function getNodeAggregateId(): NodeAggregateId
    {
        return $this->nodeAggregateId;
    }

    public function getOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->originDimensionSpacePoint;
    }

    public function equals(NodeDiscriminator $other): bool
    {
        return $this->contentStreamId->equals($other->getContentStreamId())
            && $this->getNodeAggregateId()->equals($other->getNodeAggregateId())
            && $this->getOriginDimensionSpacePoint()->equals($other->getOriginDimensionSpacePoint());
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamId' => $this->contentStreamId,
            'nodeAggregateId' => $this->nodeAggregateId,
            'originDimensionSpacePoint' => $this->originDimensionSpacePoint
        ];
    }

    public function __toString(): string
    {
        return sha1(json_encode($this));
    }
}
