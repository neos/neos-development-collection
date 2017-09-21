<?php

namespace Neos\ContentRepository\Domain\Context\DimensionSpace\Repository;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;

/**
 * The repository for content dimension value combinations
 *
 * @Flow\Scope("singleton")
 * @package Neos\ContentRepository
 */
class AllowedDimensionSubspace
{
    /**
     * @Flow\Inject
     * @var Domain\Service\ContentDimensionCombinator
     */
    protected $contentDimensionCombinator;

    /**
     * @var array|Domain\ValueObject\DimensionSpacePoint[]
     */
    protected $points;

    public function initializeObject()
    {
        $this->points = [];
        $coordinateSet = [];
        foreach ($this->contentDimensionCombinator->getAllAllowedCombinations() as $dimensionPresetCombination) {
            $presetCoordinateSet = [];
            foreach ($dimensionPresetCombination as $dimensionName => $dimensionValues) {
                if (empty($presetCoordinateSet)) {
                    foreach ($dimensionValues as $dimensionValue) {
                        $newCoordinateValues = [$dimensionName => $dimensionValue];
                        $presetCoordinateSet[json_encode($newCoordinateValues)] = $newCoordinateValues;
                    }
                } else {
                    $newCoordinates = [];
                    foreach ($presetCoordinateSet as $coordinate) {
                        foreach ($dimensionValues as $dimensionValue) {
                            $newCoordinateValues = array_merge($coordinate, [$dimensionName => $dimensionValue]);
                            $newCoordinates[json_encode($newCoordinateValues)] = $newCoordinateValues;
                        }
                    }
                    $presetCoordinateSet = $newCoordinates;
                }
            }
            $coordinateSet = Arrays::arrayMergeRecursiveOverrule($coordinateSet, $presetCoordinateSet);
        }

        foreach ($coordinateSet as $coordinates) {
            $point = new Domain\ValueObject\DimensionSpacePoint($coordinates);
            $this->points[$point->getHash()] = $point;
        }
    }

    public function contains(Domain\ValueObject\DimensionSpacePoint $point): bool
    {
        return isset($this->points[$point->getHash()]);
    }
}
