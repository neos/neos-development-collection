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

namespace Neos\ContentRepository\Core\Dimension;

/**
 * A set of content dimension value variation edges
 *
 * @implements \IteratorAggregate<ContentDimensionValueVariationEdge>
 * @internal
 */
final class ContentDimensionValueVariationEdges implements \IteratorAggregate
{
    /**
     * @var array<ContentDimensionValueVariationEdge>
     */
    private array $edges;

    public function __construct(ContentDimensionValueVariationEdge ...$edges)
    {
        $this->edges = $edges;
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public function getIterator(): \Traversable
    {
        yield from $this->edges;
    }

    public function isEmpty(): bool
    {
        return empty($this->edges);
    }
}
