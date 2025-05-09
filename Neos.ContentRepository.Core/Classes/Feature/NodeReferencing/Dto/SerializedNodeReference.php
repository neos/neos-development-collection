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
 * @api used in commands and events {@see SerializedNodeReferences}
 */
final readonly class SerializedNodeReference implements \JsonSerializable
{
    private function __construct(
        public NodeAggregateId $targetNodeAggregateId,
        public SerializedPropertyValues $properties
    ) {
    }

    public static function fromTargetAndProperties(NodeAggregateId $targetNodeAggregateId, SerializedPropertyValues $properties): self
    {
        return new self($targetNodeAggregateId, $properties);
    }

    public static function fromTarget(NodeAggregateId $targetNodeAggregateId): self
    {
        return new self($targetNodeAggregateId, SerializedPropertyValues::createEmpty());
    }

    /**
     * @param array{"target": string, "properties"?: array<string, mixed>} $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            NodeAggregateId::fromString($array['target']),
            isset($array['properties']) ? SerializedPropertyValues::fromArray($array['properties']) : SerializedPropertyValues::createEmpty()
        );
    }

    /**
     * @return array{"target": NodeAggregateId, "properties"?: SerializedPropertyValues}
     */
    public function jsonSerialize(): array
    {
        $result = ['target' => $this->targetNodeAggregateId];
        if (count($this->properties) > 0) {
            $result['properties'] = $this->properties;
        }
        return $result;
    }
}
