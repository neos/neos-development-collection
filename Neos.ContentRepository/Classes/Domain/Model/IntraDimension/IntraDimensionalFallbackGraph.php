<?php
namespace Neos\ContentRepository\Domain\Model\IntraDimension;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The intra dimensional fallback graph domain model
 * Represents the fallback mechanism within each content subgraph dimension
 */
class IntraDimensionalFallbackGraph
{
    /**
     * @var array
     */
    protected $dimensions = [];

    /**
     * @param string $dimensionName
     * @param string|null $label
     * @return ContentDimension
     */
    public function createDimension(string $dimensionName, string $label = null): ContentDimension
    {
        $dimension = new ContentDimension($dimensionName, $label);
        $this->dimensions[$dimension->getName()] = $dimension;

        return $dimension;
    }

    /**
     * @return array|ContentDimension[]
     */
    public function getDimensions(): array
    {
        return $this->dimensions;
    }

    /**
     * @param string $dimensionName
     * @return ContentDimension|null
     */
    public function getDimension(string $dimensionName)
    {
        return $this->dimensions[$dimensionName] ?: null;
    }
}
