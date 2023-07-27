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

namespace Neos\ContentRepository\Core\Feature\NodeRemoval\Dto;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\Flow\Annotations as Flow;

/**
 * @api used as part of events
 */
#[Flow\Proxy(false)]
final class DescendantAssignment implements \JsonSerializable
{
    public function __construct(
        public readonly DimensionSpacePoint $dimensionSpacePoint,
        public readonly NodeAggregateId $parentNodeAggregateId,
        public readonly OriginDimensionSpacePoint $parentOriginDimensionSpacePoint,
        public readonly NodeAggregateId $childNodeAggregateId,
        public readonly OriginDimensionSpacePoint $childOriginDimensionSpacePoint,
        public readonly ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        public readonly ?OriginDimensionSpacePoint $succeedingSiblingOriginDimensionSpacePoint
    ) {
        if ($this->succeedingSiblingNodeAggregateId && !$this->succeedingSiblingOriginDimensionSpacePoint) {
            throw new \InvalidArgumentException(
                'A succeeding sibling must be declared with BOTH node aggregate ID and origin dimension space point',
                1665065910
            );
        }
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            DimensionSpacePoint::fromArray($array['dimensionSpacePoint']),
            NodeAggregateId::fromString($array['parentNodeAggregateId']),
            OriginDimensionSpacePoint::fromArray($array['parentOriginDimensionSpacePoint']),
            NodeAggregateId::fromString($array['childNodeAggregateId']),
            OriginDimensionSpacePoint::fromArray($array['childOriginDimensionSpacePoint']),
            isset($array['succeedingSiblingNodeAggregateId'])
                ? NodeAggregateId::fromString($array['succeedingSiblingNodeAggregateId'])
                : null,
            isset($array['succeedingSiblingOriginDimensionSpacePoint'])
                ? OriginDimensionSpacePoint::fromArray($array['succeedingSiblingNodeAggregateId'])
                : null
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
