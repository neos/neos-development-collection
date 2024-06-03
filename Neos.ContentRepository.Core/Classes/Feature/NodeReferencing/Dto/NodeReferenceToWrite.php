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

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * A single node references to write, supports arbitrary objects as reference property values
 * by using {@see PropertyValuesToWrite}.
 * Will be then converted to {@see SerializedNodeReferences} inside the events and persisted commands.
 *
 * We expect the property value types to match the NodeType's property types
 * (this is validated in the command handler).
 * @api used as part of commands
 */
final readonly class NodeReferenceToWrite implements \JsonSerializable
{
    public function __construct(
        public NodeAggregateId $targetNodeAggregateId,
        public ?PropertyValuesToWrite $properties
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            NodeAggregateId::fromString($array['targetNodeAggregateId']),
            isset($array['properties']) ? PropertyValuesToWrite::fromArray($array['properties']) : null
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
