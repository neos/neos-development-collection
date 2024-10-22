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
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;

/**
 * A collection of SerializedNodeReferencesForName objects, to be used when creating reference relations.
 *
 * @implements \IteratorAggregate<SerializedNodeReferencesForName>
 * @api used in commands and events
 */
final readonly class SerializedNodeReferences implements \JsonSerializable, \IteratorAggregate
{
    /**
     * @var array<SerializedNodeReferencesForName>
     */
    public array $references;

    private function __construct(SerializedNodeReferencesForName ...$references)
    {
        $seenNames = [];
        foreach ($references as $reference) {
            $referenceNameExists = isset($seenNames[$reference->referenceName->value]);
            if ($referenceNameExists) {
                throw new \InvalidArgumentException(sprintf('You cannot set references for the same ReferenceName %s multiple times.', $reference->referenceName->value), 1718193720);
            }
            $seenNames[$reference->referenceName->value] = true;
        }
        $this->references = $references;
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    /**
     * @param array<SerializedNodeReferencesForName> $references
     */
    public static function fromReferences(array $references): self
    {
        return new self(...$references);
    }

    /**
     * @param array<int, array{"referenceName": string, "references": array<array{"target": string, "properties": mixed}>}> $namesAndReferences
     */
    public static function fromArray(array $namesAndReferences): self
    {
        $result = [];
        foreach ($namesAndReferences as $referencesByProperty) {
            $result[] = SerializedNodeReferencesForName::fromArray($referencesByProperty);
        }

        return new self(...$result);
    }

    public static function fromNameAndTargets(ReferenceName $referenceName, NodeAggregateIds $nodeAggregateIds): self
    {
        $references = array_map(static function ($nodeAggregateId) {
            return SerializedNodeReference::fromTarget($nodeAggregateId);
        }, iterator_to_array($nodeAggregateIds));

        return new self(SerializedNodeReferencesForName::fromNameAndSerializedReferences($referenceName, $references));
    }

    public static function fromReadReferences(References $references): self
    {
        $serializedReferences = [];
        $serializedReferencesByName = [];
        foreach ($references as $reference) {
            if (!isset($serializedReferencesByName[$reference->name->value])) {
                $serializedReferencesByName[$reference->name->value] = [];
            }
            $serializedReferencesByName[$reference->name->value][] = new SerializedNodeReference($reference->node->aggregateId, $reference->properties ? $reference->properties->serialized() : SerializedPropertyValues::createEmpty());
        }

        foreach ($serializedReferencesByName as $name => $referenceObjects) {
            $serializedReferences[] = SerializedNodeReferencesForName::fromNameAndSerializedReferences(ReferenceName::fromString($name), $referenceObjects);
        }

        return new self(...$serializedReferences);
    }

    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(\json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR));
    }

    public function merge(SerializedNodeReferences $serializedNodeReferences): self
    {
        return new self(...$this->getIterator(), ...$serializedNodeReferences->getIterator());
    }

    /**
     * @return \Traversable<SerializedNodeReferencesForName>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->references as $reference) {
            yield $reference;
        }
    }

    public function isEmpty(): bool
    {
        return count($this->references) === 0;
    }

    /**
     * @return ReferenceName[]
     */
    public function getReferenceNames(): array
    {
        return array_map(static fn(SerializedNodeReferencesForName $referencesForProperty) => $referencesForProperty->referenceName, $this->references);
    }

    /**
     * @return array<int, array{"referenceName": string, "references": array<array{"target": string, "properties"?: mixed}>}>
     */
    public function jsonSerialize(): array
    {
        return array_map(
            static fn (SerializedNodeReferencesForName $referencesForProperty) => $referencesForProperty->toArray(),
            $this->references
        );
    }
}
