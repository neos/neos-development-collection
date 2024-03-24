<?php

/*
 * This file is part of the Neos.ContentRepository.Core package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Node;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;

/**
 * A tuple of interdimensional relatives, consisting of optional parent and succeeding sibling ids
 * @see InterdimensionalRelatives
 *
 * @api part of events, can be evaluated by custom projections
 */
final readonly class InterdimensionalRelative implements \JsonSerializable
{
    public function __construct(
        public DimensionSpacePoint $dimensionSpacePoint,
        public ?NodeAggregateId $parentNodeAggregateId,
        public ?NodeAggregateId $succeedingSiblingNodeAggregateId
    ) {
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            DimensionSpacePoint::fromArray($values['dimensionSpacePoint']),
            $values['parentNodeAggregateId']
                ? NodeAggregateId::fromString($values['parentNodeAggregateId'])
                : null,
            $values['succeedingSiblingNodeAggregateId']
                ? NodeAggregateId::fromString($values['succeedingSiblingNodeAggregateId'])
                : null,
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
