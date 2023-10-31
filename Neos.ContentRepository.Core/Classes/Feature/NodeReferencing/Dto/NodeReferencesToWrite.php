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
 * Node references to write, supports arbitrary objects as reference property values.
 * Will be then converted to {@see SerializedNodeReferences} inside the events and persisted commands.
 *
 * We expect the value types to match the NodeType's property types (this is validated in the command handler).
 *
 * @implements \IteratorAggregate<NodeReferenceToWrite>
 * @api used as part of commands
 */
final readonly class NodeReferencesToWrite implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var array<NodeReferenceToWrite>
     */
    public array $references;

    private function __construct(NodeReferenceToWrite ...$references)
    {
        $this->references = $references;
    }

    /**
     * @param array<NodeReferenceToWrite> $references
     */
    public static function fromReferences(array $references): self
    {
        return new self(...$references);
    }

    /**
     * @param array<int,array<string,mixed>> $values
     */
    public static function fromArray(array $values): self
    {
        return new self(...array_map(
            fn (array $serializedReference): NodeReferenceToWrite
                => NodeReferenceToWrite::fromArray($serializedReference),
            $values
        ));
    }

    public static function fromNodeAggregateIds(NodeAggregateIds $nodeAggregateIds): self
    {
        return new self(...array_map(
            fn (NodeAggregateId $nodeAggregateId): NodeReferenceToWrite
                => new NodeReferenceToWrite($nodeAggregateId, null),
            iterator_to_array($nodeAggregateIds)
        ));
    }

    /**
     * @throws \JsonException
     */
    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(\json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @return \Traversable<NodeReferenceToWrite>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->references;
    }

    /**
     * @return array<NodeReferenceToWrite>
     */
    public function jsonSerialize(): array
    {
        return $this->references;
    }
}
