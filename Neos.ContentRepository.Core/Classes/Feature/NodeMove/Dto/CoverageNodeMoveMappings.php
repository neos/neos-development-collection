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

namespace Neos\ContentRepository\Core\Feature\NodeMove\Dto;

/**
 * @implements \IteratorAggregate<int,CoverageNodeMoveMapping>
 * @api DTO of {@see NodeAggregateWasMoved} event
 */
final class CoverageNodeMoveMappings implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @var array<int,CoverageNodeMoveMapping>
     */
    private array $mappings;

    /**
     * @var \ArrayIterator<int,CoverageNodeMoveMapping>
     */
    private \ArrayIterator $iterator;

    /**
     * @param array<int,CoverageNodeMoveMapping> $values
     */
    private function __construct(array $values)
    {
        $this->mappings = $values;
        $this->iterator = new \ArrayIterator($values);
    }

    /**
     * @param array<int|string,array<string,mixed>|CoverageNodeMoveMapping> $mappings
     */
    public static function fromArray(array $mappings): self
    {
        $processedMappings = [];
        foreach ($mappings as $mapping) {
            if (is_array($mapping)) {
                $processedMappings[] = CoverageNodeMoveMapping::fromArray($mapping);
            } elseif ($mapping instanceof CoverageNodeMoveMapping) {
                $processedMappings[] = $mapping;
            } else {
                /** @var mixed $mapping */
                throw new \InvalidArgumentException(
                    sprintf(
                        'Invalid NodeMoveMapping. Expected instance of %s, got: %s',
                        CoverageNodeMoveMapping::class,
                        is_object($mapping) ? get_class($mapping) : gettype($mapping)
                    ), 1547811318
                );
            }
        }
        return new self($processedMappings);
    }

    public static function create(CoverageNodeMoveMapping ...$coverageNodeMoveMappings): self
    {
        return new self($coverageNodeMoveMappings);
    }


    /**
     * @return \ArrayIterator<int,CoverageNodeMoveMapping>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    public function count(): int
    {
        return count($this->mappings);
    }

    /**
     * @return array<int,CoverageNodeMoveMapping>
     */
    public function jsonSerialize(): array
    {
        return $this->mappings;
    }
}
