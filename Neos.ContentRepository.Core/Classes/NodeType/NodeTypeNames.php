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

namespace Neos\ContentRepository\Core\NodeType;

/**
 * A collection of NodeType names
 * @api
 * @implements \IteratorAggregate<NodeTypeName>
 */
final class NodeTypeNames implements \IteratorAggregate
{
    /**
     * @var array<string, NodeTypeName>
     */
    private array $nodeTypeNames;

    private function __construct(NodeTypeName ...$nodeTypeNames)
    {
        /** @var array<string, NodeTypeName> $nodeTypeNames */
        $this->nodeTypeNames = $nodeTypeNames;
    }

    public static function with(NodeTypeName $nodeTypeName): self
    {
        return new self(...[$nodeTypeName->value => $nodeTypeName]);
    }

    /**
     * @param array<NodeTypeName> $array
     */
    public static function fromArray(array $array): self
    {
        $nodeTypeNames = [];
        foreach ($array as $nodeTypeName) {
            $nodeTypeName instanceof NodeTypeName || throw new \InvalidArgumentException(sprintf('Expected instance of %s, got: %s', NodeTypeName::class, get_debug_type($nodeTypeName)), 1713624853);
            $nodeTypeNames[$nodeTypeName->value] = $nodeTypeName;
        }
        return new self(...$nodeTypeNames);
    }

    /**
     * @param array<string> $array
     */
    public static function fromStringArray(array $array): self
    {
        return self::fromArray(array_map(
            static fn(string $serializedNodeTypeName): NodeTypeName => NodeTypeName::fromString($serializedNodeTypeName),
            $array
        ));
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public function withAdditionalNodeTypeName(NodeTypeName $nodeTypeName): self
    {
        if ($this->contain($nodeTypeName)) {
            return $this;
        }
        $nodeTypeNames = $this->nodeTypeNames;
        $nodeTypeNames[$nodeTypeName->value] = $nodeTypeName;

        return new self(...$nodeTypeNames);
    }

    /**
     * @return array<NodeTypeName>
     */
    public function toArray(): array
    {
        return array_values($this->nodeTypeNames);
    }

    /**
     * @return array<string>
     */
    public function toStringArray(): array
    {
        return array_map(static fn(NodeTypeName $nodeTypeName) => $nodeTypeName->value, $this->nodeTypeNames);
    }

    /**
     * @param \Closure(NodeTypeName): bool $callback
     */
    public function filter(\Closure $callback): self
    {
        return self::fromArray(array_filter($this->nodeTypeNames, $callback));
    }

    /**
     * @param \Closure(NodeTypeName): mixed $callback
     * @return array<mixed>
     */
    public function map(\Closure $callback): array
    {
        return array_map($callback, $this->nodeTypeNames);
    }

    public function isEmpty(): bool
    {
        return empty($this->nodeTypeNames);
    }

    public function contain(NodeTypeName $nodeTypeName): bool
    {
        return array_key_exists($nodeTypeName->value, $this->nodeTypeNames);
    }

    /**
     * @return \Traversable<NodeTypeName>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->nodeTypeNames;
    }
}
