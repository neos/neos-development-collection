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

namespace Neos\ContentRepository\Core\Feature\NodeMove\Dto;

use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * See the docs of {@see NodeAggregateWasMoved} for a full description.
 *
 * @api DTO of {@see NodeAggregateWasMoved} event
 */
final class SucceedingSiblingNodeMoveTarget implements \JsonSerializable
{
    private function __construct(
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly OriginDimensionSpacePoint $originDimensionSpacePoint
    ) {
    }

    public static function create(
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): self {
        return new self($nodeAggregateId, $originDimensionSpacePoint);
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            NodeAggregateId::fromString($array['nodeAggregateId']),
            OriginDimensionSpacePoint::fromArray($array['originDimensionSpacePoint'])
        );
    }

    /**
     * @return array<string,\JsonSerializable>
     */
    public function jsonSerialize(): array
    {
        return [
            'nodeAggregateId' => $this->nodeAggregateId,
            'originDimensionSpacePoint' => $this->originDimensionSpacePoint,
        ];
    }

    public function __toString(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }
}
