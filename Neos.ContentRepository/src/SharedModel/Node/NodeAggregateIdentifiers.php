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

namespace Neos\ContentRepository\SharedModel\Node;

use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;

/**
 * An immutable collection of NodeAggregateIdentifiers, indexed by their value
 *
 * @implements \IteratorAggregate<string,NodeAggregateIdentifier>
 */
#[Flow\Proxy(false)]
final class NodeAggregateIdentifiers implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var array<string,NodeAggregateIdentifier>
     */
    private array $nodeAggregateIdentifiers;

    /**
     * @var \ArrayIterator<string,NodeAggregateIdentifier>
     */
    private \ArrayIterator $iterator;

    private function __construct(NodeAggregateIdentifier ...$nodeAggregateIdentifiers)
    {
        /** @var array<string,NodeAggregateIdentifier> $nodeAggregateIdentifiers */
        $this->nodeAggregateIdentifiers = $nodeAggregateIdentifiers;
        $this->iterator = new \ArrayIterator($nodeAggregateIdentifiers);
    }

    public static function createEmpty(): self
    {
        return new self(...[]);
    }



    public static function create(NodeAggregateIdentifier ...$nodeAggregateIdentifiers): self
    {
        return self::fromArray($nodeAggregateIdentifiers);
    }
    /**
     * @param array<string|int,string|NodeAggregateIdentifier> $array
     */
    public static function fromArray(array $array): self
    {
        $nodeAggregateIdentifiers = [];
        foreach ($array as $serializedNodeAggregateIdentifier) {
            if ($serializedNodeAggregateIdentifier instanceof NodeAggregateIdentifier) {
                $nodeAggregateIdentifiers[(string)$serializedNodeAggregateIdentifier]
                    = $serializedNodeAggregateIdentifier;
            } else {
                $nodeAggregateIdentifiers[$serializedNodeAggregateIdentifier]
                    = NodeAggregateIdentifier::fromString($serializedNodeAggregateIdentifier);
            }
        }

        return new self(...$nodeAggregateIdentifiers);
    }

    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(\json_decode($jsonString, true));
    }

    /**
     * @param Node[] $nodes
     */
    public static function fromNodes(array $nodes): self
    {
        return self::fromArray(
            array_map(fn(Node $node) => $node->nodeAggregateIdentifier, $nodes)
        );
    }

    public function merge(self $other): self
    {
        return new self(...array_merge(
            $this->nodeAggregateIdentifiers,
            $other->getIterator()->getArrayCopy()
        ));
    }

    /**
     * @return array<string,NodeAggregateIdentifier>
     */
    public function jsonSerialize(): array
    {
        return $this->nodeAggregateIdentifiers;
    }

    public function __toString(): string
    {
        return \json_encode($this, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<int,string>
     */
    public function toStringArray(): array
    {
        return array_keys($this->nodeAggregateIdentifiers);
    }

    /**
     * @return \ArrayIterator<string,NodeAggregateIdentifier>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }
}
