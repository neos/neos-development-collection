<?php

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Dimension;

/**
 * A set of content dimension value variation edges
 *
 * @implements \IteratorAggregate<int,ContentDimensionValueVariationEdge>
 * @internal
 */
final class ContentDimensionValueVariationEdges implements \IteratorAggregate
{
    /**
     * @var array<int,ContentDimensionValueVariationEdge>
     */
    private array $edges;

    /**
     * @var \ArrayIterator<int,ContentDimensionValueVariationEdge>
     */
    private \ArrayIterator $iterator;

    /**
     * @param array<int,ContentDimensionValueVariationEdge> $array
     */
    public function __construct(array $array)
    {
        foreach ($array as $edge) {
            if (!$edge instanceof ContentDimensionValueVariationEdge) {
                throw new \InvalidArgumentException(
                    'ContentDimensionValueVariationEdges may only contain ContentDimensionValueVariationEdge objects',
                    1639661280
                );
            }
        }

        $this->edges = $array;
        $this->iterator = new \ArrayIterator($this->edges);
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    /**
     * @return \ArrayIterator<int,ContentDimensionValueVariationEdge>|ContentDimensionValueVariationEdge[]
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    public function isEmpty(): bool
    {
        return empty($this->edges);
    }
}
