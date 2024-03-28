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

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * A tuple of succeeding sibling ID to dimension space point
 * @see InterdimensionalSiblings
 *
 * @api part of events, can be evaluated by custom projections
 */
final readonly class InterdimensionalSibling implements \JsonSerializable
{
    public function __construct(
        public DimensionSpacePoint $dimensionSpacePoint,
        public ?NodeAggregateId $nodeAggregateId
    ) {
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            DimensionSpacePoint::fromArray($values['dimensionSpacePoint']),
            $values['nodeAggregateId']
                ? NodeAggregateId::fromString($values['nodeAggregateId'])
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
