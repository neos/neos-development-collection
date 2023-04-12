<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Dto;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;

/**
 * Is used to address a node within the AssetUsage package
 *
 * @internal
 */
#[Flow\Proxy(false)]
final class NodeAddress
{
    /**
     * @internal use NodeAddressFactory, if you want to create a NodeAddress
     */
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly DimensionSpacePoint $dimensionSpacePoint,
        public readonly NodeAggregateId $nodeAggregateId,
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            'NodeAddress[contentStream=%s, dimensionSpacePoint=%s, nodeAggregateId=%s]',
            $this->contentStreamId->value,
            $this->dimensionSpacePoint->toJson(),
            $this->nodeAggregateId->value
        );
    }
}
