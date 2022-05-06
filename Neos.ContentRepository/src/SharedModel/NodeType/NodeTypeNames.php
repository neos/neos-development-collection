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

namespace Neos\ContentRepository\SharedModel\NodeType;

/**
 * A collection of NodeType names
 * @api
 * @implements \IteratorAggregate<int,NodeTypeName>
 */
final class NodeTypeNames implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var array<int,NodeTypeName>
     */
    private array $nodeTypeNames;

    private function __construct(NodeTypeName ...$nodeTypeNames)
    {
        /** @var array<int,NodeTypeName> $nodeTypeNames */
        $this->nodeTypeNames = $nodeTypeNames;
    }

    /**
     * @param array<int,NodeTypeName> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(...$array);
    }

    /**
     * @param array<int,string> $array
     */
    public static function fromStringArray(array $array): self
    {
        return new self(... array_map(
            fn (string $serializedNodeTypeName): NodeTypeName => NodeTypeName::fromString($serializedNodeTypeName),
            $array
        ));
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public function withAdditionalNodeTypeName(NodeTypeName $nodeTypeName): self
    {
        if (in_array($nodeTypeName, $this->nodeTypeNames)) {
            return $this;
        }
        $nodeTypeNames = $this->nodeTypeNames;
        $nodeTypeNames[] = $nodeTypeName;

        return new self(...$nodeTypeNames);
    }

    /**
     * @return \ArrayIterator<int,NodeTypeName>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->nodeTypeNames);
    }

    /**
     * @return array<int,NodeTypeName>
     */
    public function jsonSerialize(): array
    {
        return $this->nodeTypeNames;
    }

    public function isEmpty(): bool
    {
        return empty($this->nodeTypeNames);
    }
}
