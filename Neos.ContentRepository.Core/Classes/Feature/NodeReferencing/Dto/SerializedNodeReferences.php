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
 * A collection of SerializedNodeReference objects, to be used when creating reference relations.
 *
 * @implements \IteratorAggregate<SerializedNodeReference|NodeReferenceNameToEmpty>
 * @api used in commands and events
 */
final readonly class SerializedNodeReferences implements \JsonSerializable, \IteratorAggregate
{
    /**
     * @var array<string, array<SerializedNodeReference|NodeReferenceNameToEmpty>>
     */
    public array $references;

    private function __construct(SerializedNodeReference|NodeReferenceNameToEmpty ...$references)
    {
        $existingTargets = [];
        $resultingReferences = [];
        foreach ($references as $reference) {
            $referenceNameExists = isset($resultingReferences[$reference->referenceName->value]);
            if ($reference instanceof NodeReferenceNameToEmpty) {
                if ($referenceNameExists && count($resultingReferences[$reference->referenceName->value]) > 0) {
                    throw new \InvalidArgumentException(sprintf('You cannot delete all references for the ReferenceName "%s" while also adding references for the same name.', $reference->referenceName->value), 1718193611);
                }
                $resultingReferences[$reference->referenceName->value] = [];
                continue;
            }

            if (isset($existingTargets[$reference->referenceName->value][$reference->targetNodeAggregateId->value])) {
                throw new \InvalidArgumentException(sprintf('Duplicate entry in references to write. Target "%s" already exists in collection.', $reference->targetNodeAggregateId->value), 1700150910);
            }
            if ($referenceNameExists && $resultingReferences[$reference->referenceName->value] === []) {
                throw new \InvalidArgumentException(sprintf('You cannot set references for the ReferenceName "%s" after deleting references for the same name.', $reference->referenceName->value), 1718193720);
            }

            if (!$referenceNameExists) {
                $resultingReferences[$reference->referenceName->value] = [];
            }

            $existingTargets[$reference->referenceName->value][$reference->targetNodeAggregateId->value] = true;
            $resultingReferences[$reference->referenceName->value][] = $reference;
        }
        $this->references = $resultingReferences;
    }

    /**
     * @param array<SerializedNodeReference|NodeReferenceNameToEmpty> $references
     */
    public static function fromReferences(array $references): self
    {
        return new self(...$references);
    }

    /**
     * @param array<string, array<array{"referenceName": string, "target": string, "properties"?: array<string, mixed>}>> $namesAndReferences
     */
    public static function fromArray(array $namesAndReferences): self
    {
        $result = [];
        foreach ($namesAndReferences as $name => $references) {
            if ($references === []) {
                $result[] = new NodeReferenceNameToEmpty(ReferenceName::fromString($name));
                continue;
            }

            $result = [
                ...$result,
                ...array_map(static function ($serializedReference) use ($name): SerializedNodeReference {
                    $serializedReference['referenceName'] = $name;

                    return SerializedNodeReference::fromArray($serializedReference);
                }, $references)
            ];
        }

        return new self(...$result);
    }

    public static function fromReferenceNameAndNodeAggregateIds(ReferenceName $referenceName, NodeAggregateIds $nodeAggregateIds): self
    {
        return new self(...array_map(static function ($nodeAggregateId) use ($referenceName) {
            return new SerializedNodeReference($referenceName, $nodeAggregateId, null);
        }, iterator_to_array($nodeAggregateIds)));
    }

    public static function fromReadReferences(References $references): self
    {
        $serializedReferences = [];
        foreach ($references as $reference) {
            $serializedReferences[] = new SerializedNodeReference($reference->name, $reference->node->aggregateId, $reference->properties ? $reference->properties->serialized() : SerializedPropertyValues::createEmpty());
        }

        return new self(...$serializedReferences);
    }

    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(\json_decode($jsonString, true));
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public function merge(SerializedNodeReferences $nodeReferencesToWrite): self
    {
        return new self(...$this->getIterator(), ...$nodeReferencesToWrite->getIterator());
    }

    /**
     * @return \Traversable<SerializedNodeReference|NodeReferenceNameToEmpty>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->references as $name => $references) {
            if ($references === []) {
                yield new NodeReferenceNameToEmpty(ReferenceName::fromString($name));
            }
            foreach ($references as $reference) {
                yield $reference;
            }
        }
    }

    /**
     * @return ReferenceName[]
     */
    public function getReferenceNames(): array
    {
        return array_map(static fn(string $name) => ReferenceName::fromString($name), array_keys($this->references));
    }

    public function getForReferenceName(ReferenceName $referenceName): SerializedNodeReferences
    {
        if (!isset($this->references[$referenceName->value])) {
            throw new \InvalidArgumentException(sprintf('The given ReferenceName "%s" is not set in this instance of SerializedNodeReference.', $referenceName->value), 1718264159);
        }

        if ($this->references[$referenceName->value] === []) {
            return new self(new NodeReferenceNameToEmpty($referenceName));
        }

        return new self(...$this->references[$referenceName->value]);
    }

    /**
     * @return array<string, array<array<string, mixed>>>
     */
    public function jsonSerialize(): array
    {
        return array_map(
            static function (array $referenceToWriteObjects) {
                return array_map(
                    static function (SerializedNodeReference|NodeReferenceNameToEmpty $nodeReference) {
                        return $nodeReference instanceof NodeReferenceNameToEmpty ? [] : $nodeReference->targetAndPropertiesToArray();
                    },
                    $referenceToWriteObjects
                );
            },
            $this->references
        );
    }
}
