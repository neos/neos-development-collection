<?php
namespace Neos\ContentRepository\Domain\Context\DimensionSpace;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\ContentRepository\Domain;
use Neos\Flow\Annotations as Flow;

/**
 * The repository for DimensionSpacePoints allowed by constraints
 *
 * @Flow\Scope("singleton")
 * @package Neos\ContentRepository
 */
final class AllowedDimensionSubspace
{
    /**
     * @Flow\Inject
     * @var Dimension\ContentDimensionZookeeper
     */
    protected $contentDimensionZookeeper;

    /**
     * @var array|Domain\ValueObject\DimensionSpacePoint[]
     */
    protected $points;

    public function initializeObject()
    {
        $this->points = [];

        foreach ($this->contentDimensionZookeeper->getAllowedCombinations() as $dimensionCombination) {
            $coordinates = [];
            foreach ($dimensionCombination as $contentDimensionIdentifier => $contentDimensionValue) {
                $coordinates[$contentDimensionIdentifier] = (string)$contentDimensionValue;
            }
            $point = new Domain\ValueObject\DimensionSpacePoint($coordinates);
            $this->points[$point->getHash()] = $point;
        }
    }


    /**
     * @param Domain\ValueObject\DimensionSpacePoint $point
     * @return bool
     */
    public function contains(Domain\ValueObject\DimensionSpacePoint $point): bool
    {
        return isset($this->points[$point->getHash()]);
    }

    /**
     * @return array|Domain\ValueObject\DimensionSpacePoint[]
     */
    public function getPoints(): array
    {
        return $this->points;
    }
}
