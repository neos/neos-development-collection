<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\NodeType;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Closure;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * @api
 * @implements IteratorAggregate<NodeType>
 */
final class NodeTypes implements IteratorAggregate
{
    /**
     * @var array<NodeType>
     */
    private array $nodeTypesByName;

    private function __construct(NodeType ...$nodeTypes)
    {
        $this->nodeTypesByName = $nodeTypes;
    }

    /**
     * @param array<NodeType> $nodeTypes
     */
    public static function fromArray(array $nodeTypes): self
    {
        $nodeTypesByName = [];
        foreach ($nodeTypes as $index => $nodeType) {
            $nodeType instanceof NodeType || throw new InvalidArgumentException(sprintf('expected instance of %s, got: %s at index %s', NodeType::class, get_debug_type($nodeType), $index), 1713436192);
            !array_key_exists($nodeType->name->value, $nodeTypesByName) || throw new InvalidArgumentException(sprintf('Node type "%s" is already registered at index %s', $nodeType->name->value, $index), 1713436195);
            $nodeTypesByName[$nodeType->name->value] = $nodeType;
        }
        return new self(...$nodeTypesByName);
    }

    public function with(NodeType $nodeType): self
    {
        if ($this->has($nodeType->name)) {
            throw new InvalidArgumentException(sprintf('Node type "%s" is already registered', $nodeType->name->value), 1713448999);
        }
        return new self(...[...$this->nodeTypesByName, $nodeType->name->value => $nodeType]);
    }

    public function has(NodeTypeName|string $nodeTypeName): bool
    {
        if ($nodeTypeName instanceof NodeTypeName) {
            $nodeTypeName = $nodeTypeName->value;
        }
        return array_key_exists($nodeTypeName, $this->nodeTypesByName);
    }

    public function get(NodeTypeName|string $nodeTypeName): ?NodeType
    {
        if ($nodeTypeName instanceof NodeTypeName) {
            $nodeTypeName = $nodeTypeName->value;
        }
        return $this->nodeTypesByName[$nodeTypeName] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->nodeTypesByName === [];
    }

    public function withoutAbstractNodeTypes(): self
    {
        return $this->filter(fn (NodeType $nodeType) => !$nodeType->isAbstract());
    }

    /**
     * @param Closure(NodeType): bool $callback
     */
    public function filter(Closure $callback): self
    {
        return self::fromArray(array_filter($this->nodeTypesByName, $callback));
    }

    /**
     * @param Closure(NodeType): mixed $callback
     * @return array<mixed>
     */
    public function map(Closure $callback): array
    {
        return array_map($callback, $this->nodeTypesByName);
    }

    public function getIterator(): Traversable
    {
        return yield from $this->nodeTypesByName;
    }

    /**
     * @return array<NodeType>
     */
    public function toArray(): array
    {
        return $this->nodeTypesByName;
    }
}
