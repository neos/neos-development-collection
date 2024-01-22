<?php

/*
 * This file is part of the Neos.ContentRepository.Core package.
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
     * @param array<int,CoverageNodeMoveMapping> $values
     */
    private function __construct(array $values)
    {
        $this->mappings = $values;
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
                    ),
                    1547811318
                );
            }
        }
        return new self($processedMappings);
    }

    public static function create(CoverageNodeMoveMapping ...$coverageNodeMoveMappings): self
    {
        return new self(array_values($coverageNodeMoveMappings));
    }


    /**
     * @return \Traversable<int,CoverageNodeMoveMapping>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->mappings;
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
