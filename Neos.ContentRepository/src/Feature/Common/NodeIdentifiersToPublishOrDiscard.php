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

/**
 * A collection of NodeIdentifier objects, to be used when publishing or discarding a set of nodes
 *
 * @implements \IteratorAggregate<int,NodeIdentifier>
 */
final class NodeIdentifiersToPublishOrDiscard implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @var array<int,NodeIdentifierToPublishOrDiscard>
     */
    public readonly array $nodeIdentifiers;

    private function __construct(NodeIdentifierToPublishOrDiscard ...$nodeIdentifiers)
    {
        /** @var array<int,NodeIdentifierToPublishOrDiscard> $nodeIdentifiers */
        $this->nodeIdentifiers = $nodeIdentifiers;
    }

    public static function fromNodeIdentifiers(array $nodeIdentifiers): self
    {
        return new self(...$nodeIdentifiers);
    }

    /**
     * @param array<int,array<string,mixed>> $nodeIdentifierData
     */
    public static function fromArray(array $nodeIdentifierData): self
    {
        return new self(...array_map(
            fn (array $nodeIdentifierDatum): NodeIdentifierToPublishOrDiscard =>
                NodeIdentifierToPublishOrDiscard::fromArray($nodeIdentifierDatum),
            $nodeIdentifierData
        ));
    }

    public function merge(self $other): self
    {
        return new self(...array_merge($this->nodeIdentifiers, $other->nodeIdentifiers));
    }

    /**
     * @return \ArrayIterator<int,NodeIdentifierToPublishOrDiscard>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->nodeIdentifiers);
    }

    public function count(): int
    {
        return count($this->nodeIdentifiers);
    }

    /**
     * @return array<int,NodeIdentifierToPublishOrDiscard>
     */
    public function jsonSerialize(): array
    {
        return $this->nodeIdentifiers;
    }
}
