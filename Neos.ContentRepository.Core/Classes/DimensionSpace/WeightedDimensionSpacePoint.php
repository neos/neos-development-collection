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

namespace Neos\ContentRepository\Core\DimensionSpace;

use Neos\ContentRepository\Core\Dimension;

/**
 * A weighted dimension space point
 * @internal
 */
final readonly class WeightedDimensionSpacePoint
{
    /**
     * @var array<string,Dimension\ContentDimensionValue>
     */
    public array $dimensionValues;

    public DimensionSpacePoint $dimensionSpacePoint;

    public ContentSubgraphVariationWeight $weight;

    /**
     * @param array<string,Dimension\ContentDimensionValue> $dimensionValues
     */
    public function __construct(array $dimensionValues)
    {
        $coordinates = [];
        $weightInDimensions = [];
        $contentDimensionValues = [];
        foreach ($dimensionValues as $dimensionName => $dimensionValue) {
            $contentDimensionValues[$dimensionName] = $dimensionValue;
            $coordinates[$dimensionName] = $dimensionValue->value;
            $weightInDimensions[$dimensionName] = $dimensionValue->specializationDepth;
        }
        $this->dimensionValues = $contentDimensionValues;
        $this->dimensionSpacePoint = DimensionSpacePoint::fromArray($coordinates);
        $this->weight = new ContentSubgraphVariationWeight($weightInDimensions);
    }

    public function getIdentityHash(): string
    {
        return $this->dimensionSpacePoint->hash;
    }
}
