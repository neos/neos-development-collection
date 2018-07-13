<?php

namespace Neos\ContentRepository\DimensionSpace\DimensionSpace;

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\DimensionSpace\Dimension;

/**
 * The repository for DimensionSpacePoints allowed by constraints
 */
final class AllowedDimensionSubspace
{
    /**
     * @var Dimension\ContentDimensionZookeeper
     */
    protected $contentDimensionZookeeper;

    /**
     * @var DimensionSpacePointSet
     */
    protected $points;

    public function __construct(Dimension\ContentDimensionZookeeper $contentDimensionZookeeper)
    {
        $this->contentDimensionZookeeper = $contentDimensionZookeeper;
    }

    public function contains(DimensionSpacePoint $point): bool
    {
        if (is_null($this->points)) {
            $this->initializePoints();
        }

        return $this->points->contains($point);
    }

    public function getPoints(): DimensionSpacePointSet
    {
        return $this->points;
    }

    protected function initializePoints(): void
    {
        $points = [];

        foreach ($this->contentDimensionZookeeper->getAllowedCombinations() as $dimensionCombination) {
            $coordinates = [];
            foreach ($dimensionCombination as $contentDimensionIdentifier => $contentDimensionValue) {
                $coordinates[$contentDimensionIdentifier] = (string)$contentDimensionValue;
            }
            $point = new DimensionSpacePoint($coordinates);
            $points[$point->getHash()] = $point;
        }

        $this->points = new DimensionSpacePointSet($points);
    }
}
