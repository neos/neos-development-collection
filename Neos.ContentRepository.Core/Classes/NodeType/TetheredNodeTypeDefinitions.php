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
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Traversable;

/**
 * @api
 * @implements IteratorAggregate<TetheredNodeTypeDefinition>
 */
final class TetheredNodeTypeDefinitions implements IteratorAggregate
{
    /**
     * @var array<string, TetheredNodeTypeDefinition>
     */
    private array $tetheredNodeTypeDefinitions;

    private function __construct(TetheredNodeTypeDefinition ...$tetheredNodeTypeDefinitions)
    {
        $this->tetheredNodeTypeDefinitions = $tetheredNodeTypeDefinitions;
    }

    /**
     * @param array<TetheredNodeTypeDefinition> $tetheredNodeTypeDefinitions
     */
    public static function fromArray(array $tetheredNodeTypeDefinitions): self
    {
        $tetheredNodeTypeDefinitionDefinitionByName = [];
        foreach ($tetheredNodeTypeDefinitions as $index => $tetheredNodeTypeDefinitionDefinition) {
            $tetheredNodeTypeDefinitionDefinition instanceof TetheredNodeTypeDefinition || throw new InvalidArgumentException(sprintf('expected instance of %s, got: %s at index %s', TetheredNodeTypeDefinition::class, get_debug_type($tetheredNodeTypeDefinitionDefinition), $index), 1713549511);
            !array_key_exists($tetheredNodeTypeDefinitionDefinition->name->value, $tetheredNodeTypeDefinitionDefinitionByName) || throw new InvalidArgumentException(sprintf('Tethered node type definition with name "%s" is already registered at index %s', $tetheredNodeTypeDefinitionDefinition->name->value, $index), 1713549527);
            $tetheredNodeTypeDefinitionDefinitionByName[$tetheredNodeTypeDefinitionDefinition->name->value] = $tetheredNodeTypeDefinitionDefinition;
        }
        return new self(...$tetheredNodeTypeDefinitionDefinitionByName);
    }

    public function with(TetheredNodeTypeDefinition $tetheredNodeTypeDefinitionDefinition): self
    {
        if ($this->contain($tetheredNodeTypeDefinitionDefinition->name)) {
            throw new InvalidArgumentException(sprintf('Tethered node type definition "%s" is already registered', $tetheredNodeTypeDefinitionDefinition->name->value), 1713549543);
        }
        return new self(...[...$this->tetheredNodeTypeDefinitions, $tetheredNodeTypeDefinitionDefinition->name->value => $tetheredNodeTypeDefinitionDefinition]);
    }

    public function get(NodeName|string $nodeName): ?TetheredNodeTypeDefinition
    {
        if ($nodeName instanceof NodeName) {
            $nodeName = $nodeName->value;
        }
        return $this->tetheredNodeTypeDefinitions[$nodeName] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->tetheredNodeTypeDefinitions === [];
    }

    public function contain(NodeName|string $nodeName): bool
    {
        if ($nodeName instanceof NodeName) {
            $nodeName = $nodeName->value;
        }
        return array_key_exists($nodeName, $this->tetheredNodeTypeDefinitions);
    }

    /**
     * @param Closure(TetheredNodeTypeDefinition): bool $callback
     */
    public function filter(Closure $callback): self
    {
        return self::fromArray(array_filter($this->tetheredNodeTypeDefinitions, $callback));
    }

    /**
     * @param Closure(TetheredNodeTypeDefinition): mixed $callback
     * @return array<mixed>
     */
    public function map(Closure $callback): array
    {
        return array_map($callback, $this->tetheredNodeTypeDefinitions);
    }

    public function getIterator(): Traversable
    {
        return yield from $this->tetheredNodeTypeDefinitions;
    }

    /**
     * @return array<TetheredNodeTypeDefinition>
     */
    public function toArray(): array
    {
        return $this->tetheredNodeTypeDefinitions;
    }
}
