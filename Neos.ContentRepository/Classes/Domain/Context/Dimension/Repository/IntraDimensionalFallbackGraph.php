<?php

namespace Neos\ContentRepository\Domain\Context\Dimension\Repository;

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
use Neos\ContentRepository\Service\Exception\InvalidDimensionConfigurationException;
use Neos\Flow\Annotations as Flow;

/**
 * The intra dimensional fallback graph domain model
 * Represents the fallback mechanism within each content subgraph dimension
 *
 * @Flow\Scope("singleton")
 */
class IntraDimensionalFallbackGraph
{
    /**
     * @Flow\Inject
     * @var Domain\Service\ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * @var array
     */
    protected $dimensions = [];

    /**
     * @var array
     */
    protected $prioritizedContentDimensions = [];

    public function initializeObject()
    {
        foreach ($this->contentDimensionPresetSource->getAllPresets() as $dimensionName => $dimensionConfiguration) {
            $presetDimension = $this->createDimension($dimensionName, $dimensionConfiguration['label'] ?? null);
            foreach ($dimensionConfiguration['presets'] as $valueName => $valueConfiguration) {
                if (!isset($valueConfiguration['values'])) {
                    continue;
                }
                $fallbackConfiguration = array_slice($valueConfiguration['values'], 0, 2);
                if (isset($fallbackConfiguration[1])) {
                    if ($presetDimension->getValue($fallbackConfiguration[1])) {
                        $fallbackValue = $presetDimension->getValue($fallbackConfiguration[1]);
                    } else {
                        throw new InvalidDimensionConfigurationException('Unknown fallback value ' . $fallbackConfiguration[1] . ' was for defined for value ' . $fallbackConfiguration[0], 1487617770);
                    }
                } else {
                    $fallbackValue = null;
                }
                $presetDimension->createValue($fallbackConfiguration[0], $fallbackValue);
            }
            $this->prioritizedContentDimensions[] = $presetDimension;
        }
    }

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

    /**
     * @return array|ContentDimension[]
     */
    public function getPrioritizedContentDimensions(): array
    {
        return $this->prioritizedContentDimensions;
    }
}
