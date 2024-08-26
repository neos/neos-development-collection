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

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;

/**
 * Node references to write, supports arbitrary objects as reference property values.
 * Will be then converted to {@see SerializedNodeReferences} inside the events and persisted commands.
 *
 * We expect the value types to match the NodeType's property types (this is validated in the command handler).
 *
 * @implements \IteratorAggregate<SerializedNodeReference|NodeReferenceNameToEmpty>
 * @api used as part of commands
 */
final readonly class NodeReferencesToWrite implements \JsonSerializable, \IteratorAggregate
{
    /**
     * @var array<string, array<NodeReferenceToWrite>>
     */
    public array $references;

    private function __construct(NodeReferenceToWrite|NodeReferenceNameToEmpty ...$references)
    {
        $resultingReferences = [];
        foreach ($references as $reference) {
            $referenceNameExists = isset($resultingReferences[$reference->referenceName->value]);
            if ($reference instanceof NodeReferenceNameToEmpty) {
                if ($referenceNameExists && count($resultingReferences[$reference->referenceName->value]) > 0) {
                    throw new \InvalidArgumentException(sprintf('You cannot set references for the ReferenceName %s while also deleting references for the same name.', $reference->referenceName->value), 1718193611);
                }
                $resultingReferences[$reference->referenceName->value] = [];
                continue;
            }
            if ($referenceNameExists && count($resultingReferences[$reference->referenceName->value]) === 0) {
                throw new \InvalidArgumentException(sprintf('You cannot set references for the ReferenceName %s while also deleting references for the same name.', $reference->referenceName->value), 1718193720);
            }
            if (!$referenceNameExists) {
                $resultingReferences[$reference->referenceName->value] = [];
            }
            $resultingReferences[$reference->referenceName->value][] = $reference;
        }
        $this->references = $resultingReferences;
    }

    /**
     * @param array<NodeReferenceToWrite> $references
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
                ...array_map(static function ($serializedReference) use ($name) {
                    $serializedReference['referenceName'] = $name;
                    return NodeReferenceToWrite::fromArray($serializedReference);
                }, $references)
            ];
        }

        return new self(...$result);
    }

    public static function fromReferenceNameAndNodeAggregateIds(ReferenceName $referenceName, NodeAggregateIds $nodeAggregateIds): self
    {
        return new self(...array_map(static function ($nodeAggregateId) use ($referenceName) {
            return new NodeReferenceToWrite($referenceName, $nodeAggregateId, null);
        }, iterator_to_array($nodeAggregateIds)));
    }

    /**
     * @throws \JsonException
     */
    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(\json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR));
    }

    public function merge(NodeReferencesToWrite $nodeReferencesToWrite): self
    {
        return new self(...$this->getIterator(), ...$nodeReferencesToWrite->getIterator());
    }

    /**
     * @return \Traversable<NodeReferenceToWrite|NodeReferenceNameToEmpty>
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
     * @return array<string, array<array<string, mixed>>>
     */
    public function jsonSerialize(): array
    {
        return array_map(static fn(array $referenceToWriteObjects) => array_map(static fn(NodeReferenceToWrite $nodeReference) => $nodeReference->targetAndPropertiesToArray(), $referenceToWriteObjects), $this->references);
    }
}
