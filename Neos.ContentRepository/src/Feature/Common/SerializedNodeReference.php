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

namespace Neos\ContentRepository\Feature\Common;

use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;

/**
 * "Raw" / Serialized node reference as saved in the event log // in projections.
 */
final class SerializedNodeReference implements \JsonSerializable
{
    public function __construct(
        public readonly NodeAggregateIdentifier $targetNodeAggregateIdentifier,
        public readonly ?SerializedPropertyValues $properties
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            NodeAggregateIdentifier::fromString($array['targetNodeAggregateIdentifier']),
            $array['properties'] ? SerializedPropertyValues::fromArray($array['properties']) : null
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'targetNodeAggregateIdentifier' => $this->targetNodeAggregateIdentifier,
            'properties' => $this->properties
        ];
    }
}
