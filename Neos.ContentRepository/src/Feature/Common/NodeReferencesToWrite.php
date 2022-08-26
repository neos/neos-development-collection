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

use JetBrains\PhpStorm\Internal\TentativeType;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifiers;

/**
 * Node references to write, supports arbitrary objects as reference property values.
 * Will be then converted to {@see SerializedNodeReferences} inside the events and persisted commands.
 *
 * We expect the value types to match the NodeType's property types (this is validated in the command handler).
 *
 * @implements \IteratorAggregate<int,NodeReferenceToWrite>
 */
final class NodeReferencesToWrite implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var array<int,NodeReferenceToWrite>
     */
    public readonly array $references;

    private function __construct(NodeReferenceToWrite ...$references)
    {
        /** @var array<int,NodeReferenceToWrite> $references */
        $this->references = $references;
    }

    /**
     * @param array<int,NodeReferenceToWrite> $references
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

    public static function fromNodeAggregateIdentifiers(NodeAggregateIdentifiers $nodeAggregateIdentifiers): self
    {
        return new self(...array_map(
            fn (NodeAggregateIdentifier $nodeAggregateIdentifier): NodeReferenceToWrite
                => new NodeReferenceToWrite($nodeAggregateIdentifier, null),
            $nodeAggregateIdentifiers->getIterator()->getArrayCopy()
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
     * @return \ArrayIterator<int,NodeReferenceToWrite>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->references);
    }

    /**
     * @return array<int,NodeReferenceToWrite>
     */
    public function jsonSerialize(): array
    {
        return $this->references;
    }
}
