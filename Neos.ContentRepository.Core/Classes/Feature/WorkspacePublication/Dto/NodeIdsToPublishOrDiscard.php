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

namespace Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto;

/**
 * A collection of NodeIdToPublish objects, to be used when publishing or discarding a set of nodes
 *
 * @implements \IteratorAggregate<int,NodeIdToPublishOrDiscard>
 * @api used as part of commands
 */
final class NodeIdsToPublishOrDiscard implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @var array<NodeIdToPublishOrDiscard>
     */
    public readonly array $nodeIds;

    /**
     * @param array<NodeIdToPublishOrDiscard> $nodeIds
     */
    private function __construct(array $nodeIds)
    {
        $this->nodeIds = $nodeIds;
    }

    public static function create(NodeIdToPublishOrDiscard ...$nodeIds): self
    {
        return new self($nodeIds);
    }

    /**
     * @param array<int,array<string,mixed>> $nodeIdData
     */
    public static function fromArray(array $nodeIdData): self
    {
        return new self(array_map(
            fn (array $nodeIdDatum): NodeIdToPublishOrDiscard => NodeIdToPublishOrDiscard::fromArray($nodeIdDatum),
            $nodeIdData
        ));
    }

    public function merge(self $other): self
    {
        return new self(array_merge($this->nodeIds, $other->nodeIds));
    }

    /**
     * @return \ArrayIterator<int,NodeIdToPublishOrDiscard>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->nodeIds);
    }

    public function count(): int
    {
        return count($this->nodeIds);
    }

    /**
     * @return array<int,NodeIdToPublishOrDiscard>
     */
    public function jsonSerialize(): array
    {
        return $this->nodeIds;
    }
}
