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
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Traversable;

/**
 * @api
 * @implements IteratorAggregate<ReferenceDefinition>
 */
final class ReferenceDefinitions implements IteratorAggregate
{
    /**
     * @var array<string, ReferenceDefinition>
     */
    private array $referenceDefinitions;

    private function __construct(ReferenceDefinition ...$referenceDefinitions)
    {
        /** @var array<string, ReferenceDefinition> $referenceDefinitions */
        $this->referenceDefinitions = $referenceDefinitions;
    }

    /**
     * @param array<ReferenceDefinition> $referenceDefinitions
     */
    public static function fromArray(array $referenceDefinitions): self
    {
        $referenceDefinitionByName = [];
        foreach ($referenceDefinitions as $index => $referenceDefinition) {
            $referenceDefinition instanceof ReferenceDefinition || throw new InvalidArgumentException(sprintf('expected instance of %s, got: %s at index %s', ReferenceDefinition::class, get_debug_type($referenceDefinition), $index), 1713542797);
            !array_key_exists($referenceDefinition->name->value, $referenceDefinitionByName) || throw new InvalidArgumentException(sprintf('Reference definition "%s" is already registered at index %s', $referenceDefinition->name->value, $index), 1713542800);
            $referenceDefinitionByName[$referenceDefinition->name->value] = $referenceDefinition;
        }
        return new self(...$referenceDefinitionByName);
    }

    public function with(ReferenceDefinition $referenceDefinition): self
    {
        if ($this->contain($referenceDefinition->name)) {
            throw new InvalidArgumentException(sprintf('Reference definition "%s" is already registered', $referenceDefinition->name->value), 1713542132);
        }
        return new self(...[...$this->referenceDefinitions, $referenceDefinition->name->value => $referenceDefinition]);
    }

    public function get(ReferenceName|string $referenceName): ?ReferenceDefinition
    {
        if ($referenceName instanceof ReferenceName) {
            $referenceName = $referenceName->value;
        }
        return $this->referenceDefinitions[$referenceName] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->referenceDefinitions === [];
    }

    public function contain(ReferenceName|string $referenceName): bool
    {
        if ($referenceName instanceof ReferenceName) {
            $referenceName = $referenceName->value;
        }
        return array_key_exists($referenceName, $this->referenceDefinitions);
    }

    /**
     * @param Closure(ReferenceDefinition): bool $callback
     */
    public function filter(Closure $callback): self
    {
        return self::fromArray(array_filter($this->referenceDefinitions, $callback));
    }

    /**
     * @param Closure(ReferenceDefinition): mixed $callback
     * @return array<mixed>
     */
    public function map(Closure $callback): array
    {
        return array_map($callback, $this->referenceDefinitions);
    }

    public function getIterator(): Traversable
    {
        return yield from $this->referenceDefinitions;
    }

    /**
     * @return array<ReferenceDefinition>
     */
    public function toArray(): array
    {
        return $this->referenceDefinitions;
    }
}
