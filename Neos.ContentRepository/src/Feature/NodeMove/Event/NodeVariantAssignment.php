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

namespace Neos\ContentRepository\Feature\NodeMove\Event;

use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;

/**
 * A node variant assignment, identifying a node variant by node aggregate identifier and origin dimension space point.
 *
 * This is used in structural operations like node move to assign a new node within the same content stream
 * as a new parent, sibling etc.
 *
 * In case of move, this is the "target node" underneath which or next to which we want to move our source.
 */
#[Flow\Proxy(false)]
final class NodeVariantAssignment implements \JsonSerializable
{
    public function __construct(
        public readonly NodeAggregateIdentifier $nodeAggregateIdentifier,
        public readonly OriginDimensionSpacePoint $originDimensionSpacePoint
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function createFromArray(array $array): self
    {
        return new self(
            NodeAggregateIdentifier::fromString($array['nodeAggregateIdentifier']),
            OriginDimensionSpacePoint::fromArray($array['originDimensionSpacePoint'])
        );
    }

    /**
     * @return array<string,\JsonSerializable>
     */
    public function jsonSerialize(): array
    {
        return [
            'nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            'originDimensionSpacePoint' => $this->originDimensionSpacePoint,
        ];
    }

    public function __toString(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }
}
