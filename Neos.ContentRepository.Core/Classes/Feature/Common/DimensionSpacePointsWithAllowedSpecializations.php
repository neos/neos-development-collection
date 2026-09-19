<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Common;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\DimensionSpace\AbstractDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;

/**
 * A set of dimension space points with allowed specializations
 *
 * @internal To be used in dimension space points for fallback constraint checks
 *
 * @implements \IteratorAggregate<string,DimensionSpacePointWithAllowedSpecializations>
 */
final readonly class DimensionSpacePointsWithAllowedSpecializations implements \IteratorAggregate
{
    /**
     * @param array<string,DimensionSpacePointWithAllowedSpecializations> $items The items to run constraint checks against, indexed by DSP hash
     */
    private function __construct(
        private array $items,
    ) {
    }

    /**
     * @param DimensionSpacePointWithAllowedSpecializations ...$items The items to run constraint checks against
     */
    public static function create(
        DimensionSpacePointWithAllowedSpecializations ...$items,
    ): self {
        $values = [];
        foreach ($items as $item) {
            $values[$item->dimensionSpacePoint->hash] = $item;
        }
        return new self($values);
    }

    public function constraint(AbstractDimensionSpacePoint $dimensionSpacePoint): bool
    {
        return array_key_exists($dimensionSpacePoint->hash, $this->items);
    }

    public function getAllowedSpecializations(AbstractDimensionSpacePoint $dimensionSpacePoint): DimensionSpacePointSet
    {
        return $this->items[$dimensionSpacePoint->hash]->allowedSpecializations ?? DimensionSpacePointSet::fromArray([]);
    }

    /**
     * @return \Traversable<string,DimensionSpacePointWithAllowedSpecializations>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
