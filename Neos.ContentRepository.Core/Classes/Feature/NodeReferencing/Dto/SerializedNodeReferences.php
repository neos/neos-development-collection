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

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;

/**
 * A collection of SerializedNodeReference objects, to be used when creating reference relations.
 *
 * @implements \IteratorAggregate<SerializedNodeReference>
 * @internal
 */
final readonly class SerializedNodeReferences implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @var array<SerializedNodeReference>
     */
    public array $references;

    private function __construct(SerializedNodeReference ...$references)
    {
        $existingTargets = [];
        foreach ($references as $reference) {
            if (isset($existingTargets[$reference->targetNodeAggregateId->value])) {
                throw new \InvalidArgumentException(sprintf('Duplicate entry in references to write. Target "%s" already exists in collection.', $reference->targetNodeAggregateId->value), 1700150910);
            }
            $existingTargets[$reference->targetNodeAggregateId->value] = true;
        }
        $this->references = $references;
    }

    /**
     * @param array<SerializedNodeReference> $references
     */
    public static function fromReferences(array $references): self
    {
        return new self(...$references);
    }

    /**
     * @param array<array<string,mixed>> $referenceData
     */
    public static function fromArray(array $referenceData): self
    {
        return new self(...array_map(
            fn (array $referenceDatum): SerializedNodeReference => SerializedNodeReference::fromArray($referenceDatum),
            $referenceData
        ));
    }

    public static function fromNodeAggregateIds(NodeAggregateIds $nodeAggregateIds): self
    {
        return new self(...array_map(
            static fn (NodeAggregateId $nodeAggregateId): SerializedNodeReference
                => new SerializedNodeReference($nodeAggregateId, null),
            iterator_to_array($nodeAggregateIds)
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
     * @return \Traversable<SerializedNodeReference>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->references;
    }

    public function count(): int
    {
        return count($this->references);
    }

    /**
     * @return array<SerializedNodeReference>
     */
    public function jsonSerialize(): array
    {
        return $this->references;
    }
}
