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
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifiers;

/**
 * A collection of SerializedNodeReference objects, to be used when creating reference relations.
 *
 * @implements \IteratorAggregate<int,SerializedNodeReference>
 * @api used as part of commands/events
 */
final class SerializedNodeReferences implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @var array<int,SerializedNodeReference>
     */
    public readonly array $references;

    private function __construct(SerializedNodeReference ...$references)
    {
        /** @var array<int,SerializedNodeReference> $references */
        $this->references = $references;
    }

    /**
     * @param array<int,SerializedNodeReference> $references
     */
    public static function fromReferences(array $references): self
    {
        return new self(...$references);
    }

    /**
     * @param array<int,array<string,mixed>> $referenceData
     */
    public static function fromArray(array $referenceData): self
    {
        return new self(...array_map(
            fn (array $referenceDatum): SerializedNodeReference => SerializedNodeReference::fromArray($referenceDatum),
            $referenceData
        ));
    }

    public static function fromNodeAggregateIdentifiers(NodeAggregateIdentifiers $nodeAggregateIdentifiers): self
    {
        return new self(...array_map(
            static fn (NodeAggregateIdentifier $nodeAggregateIdentifier): SerializedNodeReference
            => new SerializedNodeReference($nodeAggregateIdentifier, null),
            $nodeAggregateIdentifiers->getIterator()->getArrayCopy()
        ));
    }

    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(\json_decode($jsonString, true));
    }

    public function merge(self $other): self
    {
        return new self(...array_merge($this->references, $other->references));
    }

    /**
     * @return \ArrayIterator<int,SerializedNodeReference>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->references);
    }

    public function count(): int
    {
        return count($this->references);
    }

    /**
     * @return array<int,SerializedNodeReference>
     */
    public function jsonSerialize(): array
    {
        return $this->references;
    }
}
