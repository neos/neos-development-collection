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
 * A single node references to write, supports arbitrary objects as reference property values
 * by using {@see PropertyValuesToWrite}.
 * Will be then converted to {@see SerializedNodeReferences} inside the events and persisted commands.
 *
 * We expect the property value types to match the NodeType's property types
 * (this is validated in the command handler).
 * @api used as part of commands
 */
final class NodeReferenceToWrite implements \JsonSerializable
{
    public function __construct(
        public readonly NodeAggregateIdentifier $targetNodeAggregateIdentifier,
        public readonly ?PropertyValuesToWrite $properties
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            NodeAggregateIdentifier::fromString($array['targetNodeAggregateIdentifier']),
            isset($array['properties']) ? PropertyValuesToWrite::fromArray($array['properties']) : null
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
