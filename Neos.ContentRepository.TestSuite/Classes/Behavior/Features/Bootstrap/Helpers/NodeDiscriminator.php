<?php

/*
 * This file is part of the Neos.ContentRepository.TestSuite package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

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
final readonly class NodeDiscriminator implements \JsonSerializable
{
    private function __construct(
        public ContentStreamId $contentStreamId,
        public NodeAggregateId $nodeAggregateId,
        public OriginDimensionSpacePoint $originDimensionSpacePoint
    ) {
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
        return new self(
            $node->subgraphIdentity->contentStreamId,
            $node->nodeAggregateId,
            $node->originDimensionSpacePoint
        );
    }

    public function equals(self $other): bool
    {
        return $this->contentStreamId->equals($other->contentStreamId)
            && $this->nodeAggregateId->equals($other->nodeAggregateId)
            && $this->originDimensionSpacePoint->equals($other->originDimensionSpacePoint);
    }

    /**
     * @return array<string,mixed>
     */
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
