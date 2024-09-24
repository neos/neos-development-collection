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

namespace Neos\ContentRepository\Core\SharedModel\Node;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;

/**
 * An immutable collection of NodeAggregateIds, indexed by their value
 *
 * @implements \IteratorAggregate<string,NodeAggregateId>
 * @api
 */
final class NodeAggregateIds implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var array<string,NodeAggregateId>
     */
    private array $nodeAggregateIds;

    private function __construct(NodeAggregateId ...$nodeAggregateIds)
    {
        /** @var array<string,NodeAggregateId> $nodeAggregateIds */
        $this->nodeAggregateIds = $nodeAggregateIds;
    }

    public static function createEmpty(): self
    {
        return new self(...[]);
    }

    public static function create(NodeAggregateId ...$nodeAggregateIds): self
    {
        return self::fromArray($nodeAggregateIds);
    }

    /**
     * @param array<string|int,string|NodeAggregateId> $array
     */
    public static function fromArray(array $array): self
    {
        $nodeAggregateIds = [];
        foreach ($array as $serializedNodeAggregateId) {
            if ($serializedNodeAggregateId instanceof NodeAggregateId) {
                $nodeAggregateIds[$serializedNodeAggregateId->value] = $serializedNodeAggregateId;
            } else {
                $nodeAggregateIds[$serializedNodeAggregateId] = NodeAggregateId::fromString($serializedNodeAggregateId);
            }
        }

        return new self(...$nodeAggregateIds);
    }

    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(\json_decode($jsonString, true));
    }

    public static function fromNodes(Nodes $nodes): self
    {
        return $nodes->toNodeAggregateIds();
    }

    public function merge(self $other): self
    {
        return new self(...array_merge(
            $this->nodeAggregateIds,
            $other->nodeAggregateIds
        ));
    }

    public function contain(NodeAggregateId $nodeAggregateId): bool
    {
        return array_key_exists($nodeAggregateId->value, $this->nodeAggregateIds);
    }

    /**
     * @return array<string,NodeAggregateId>
     */
    public function jsonSerialize(): array
    {
        return $this->nodeAggregateIds;
    }

    public function toJson(): string
    {
        return \json_encode($this, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<int,string>
     */
    public function toStringArray(): array
    {
        return array_keys($this->nodeAggregateIds);
    }

    /**
     * @return \Traversable<string,NodeAggregateId>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->nodeAggregateIds;
    }

    /**
     * @template T
     * @param \Closure(NodeAggregateId $nodeAggregateId): T $callback
     * @return list<T>
     */
    public function map(\Closure $callback): array
    {
        return array_map($callback, $this->nodeAggregateIds);
    }
}
