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

namespace Neos\ContentRepository\Core\Feature\NodeReferencing\Dto;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * "Raw" / Serialized node reference as saved in the event log // in projections.
 *
 * @internal
 */
final readonly class SerializedNodeReference implements \JsonSerializable
{
    public function __construct(
        public NodeAggregateId $targetNodeAggregateId,
        public ?SerializedPropertyValues $properties
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            NodeAggregateId::fromString($array['targetNodeAggregateId']),
            $array['properties'] ? SerializedPropertyValues::fromArray($array['properties']) : null
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'targetNodeAggregateId' => $this->targetNodeAggregateId,
            'properties' => $this->properties
        ];
    }
}
