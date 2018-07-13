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
 * A weighted dimension space point
 */
final class WeightedDimensionSpacePoint
{
    /**
     * @var array|Dimension\ContentDimensionValue[]
     */
    protected $dimensionValues = [];

    /**
     * @var DimensionSpacePoint
     */
    protected $dimensionSpacePoint;

    /**
     * @var ContentSubgraphVariationWeight
     */
    protected $weight;


    /**
     * @param array|Dimension\ContentDimensionValue[] $dimensionValues
     */
    public function __construct(array $dimensionValues)
    {
        $coordinates = [];
        $weightInDimensions = [];
        foreach ($dimensionValues as $dimensionName => $dimensionValue) {
            $this->dimensionValues[$dimensionName] = $dimensionValue;
            $coordinates[$dimensionName] = $dimensionValue->getValue();
            $weightInDimensions[$dimensionName] = $dimensionValue->getSpecializationDepth();
        }
        $this->dimensionSpacePoint = new DimensionSpacePoint($coordinates);
        $this->weight = new ContentSubgraphVariationWeight($weightInDimensions);
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getIdentifier(): DimensionSpacePoint
    {
        return $this->getDimensionSpacePoint();
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    /**
     * @return array|Dimension\ContentDimensionValue[]
     */
    public function getDimensionValues(): array
    {
        return $this->dimensionValues;
    }

    /**
     * @param Dimension\ContentDimensionIdentifier $dimensionIdentifier
     * @return Dimension\ContentDimensionValue
     */
    public function getDimensionValue(Dimension\ContentDimensionIdentifier $dimensionIdentifier
    ): ?Dimension\ContentDimensionValue {
        return $this->dimensionValues[(string)$dimensionIdentifier] ?? null;
    }

    /**
     * @return string
     */
    public function getIdentityHash(): string
    {
        return $this->dimensionSpacePoint->getHash();
    }

    /**
     * @return ContentSubgraphVariationWeight
     */
    public function getWeight(): ContentSubgraphVariationWeight
    {
        return $this->weight;
    }
}
