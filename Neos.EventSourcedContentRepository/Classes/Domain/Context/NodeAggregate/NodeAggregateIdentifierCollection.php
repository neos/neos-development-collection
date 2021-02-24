<?php

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;

/**
 * An immutable collection of NodeAggregateIdentifiers
 */
final class NodeAggregateIdentifierCollection implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var array|NodeAggregateIdentifier[]
     */
    private array $nodeAggregateIdentifiers = [];

    public function __construct(array $nodeAggregateIdentifiers)
    {
        foreach ($nodeAggregateIdentifiers as $nodeAggregateIdentifier) {
            if (!$nodeAggregateIdentifier instanceof NodeAggregateIdentifier) {
                throw new \InvalidArgumentException('NodeAggregateIdentifiers objects can only be composed of NodeAggregateIdentifiers.', 1614190761);
            }
        }

        $this->nodeAggregateIdentifiers = $nodeAggregateIdentifiers;
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    public static function fromArray(array $array): self
    {
        $nodeAggregateIdentifiers = [];
        foreach ($array as $i => $rawNodeAggregateIdentifier) {
            $nodeAggregateIdentifiers[$i] = NodeAggregateIdentifier::fromString($rawNodeAggregateIdentifier);
        }

        return new self($nodeAggregateIdentifiers);
    }

    public function jsonSerialize(): array
    {
        return $this->nodeAggregateIdentifiers;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->nodeAggregateIdentifiers);
    }
}
