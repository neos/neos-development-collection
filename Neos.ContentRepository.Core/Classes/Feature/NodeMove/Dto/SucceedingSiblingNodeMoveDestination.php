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
 * This case is used for most cases:
 *
 * - move nodes on the same level (ordering between siblings)
 *   ```
 *   - <-- destination
 *   - node1
 *   - node2
 *   - node3 <-- source: we want to move this node
 *   ```
 *   => `SucceedingSiblingNodeMoveDestination(node1)`
 *
 * - move nodes to a different parent, and before a specific sibling.
 *   ```
 *   - node1
 *     - <-- destination
 *     - node2
 *   - node3 <-- source: we want to move this node
 *   ```
 *   => `SucceedingSiblingNodeMoveDestination(node2)`
 *
 * @api DTO of {@see NodeAggregateWasMoved} event
 */
final class SucceedingSiblingNodeMoveDestination implements \JsonSerializable
{
    private function __construct(
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly OriginDimensionSpacePoint $originDimensionSpacePoint,
        public readonly NodeAggregateId $parentNodeAggregateId,
        public readonly OriginDimensionSpacePoint $parentOriginDimensionSpacePoint
    ) {
    }

    public static function create(
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        NodeAggregateId $parentNodeAggregateId,
        OriginDimensionSpacePoint $parentOriginDimensionSpacePoint
    ): self {
        return new self(
            $nodeAggregateId,
            $originDimensionSpacePoint,
            $parentNodeAggregateId,
            $parentOriginDimensionSpacePoint
        );
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            NodeAggregateId::fromString($array['nodeAggregateId']),
            OriginDimensionSpacePoint::fromArray($array['originDimensionSpacePoint']),
            NodeAggregateId::fromString($array['parentNodeAggregateId']),
            OriginDimensionSpacePoint::fromArray($array['parentOriginDimensionSpacePoint'])
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
            'parentNodeAggregateId' => $this->parentNodeAggregateId,
            'parentOriginDimensionSpacePoint' => $this->parentOriginDimensionSpacePoint,
        ];
    }

    public function __toString(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }
}
