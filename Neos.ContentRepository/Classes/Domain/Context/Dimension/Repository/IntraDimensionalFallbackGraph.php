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
use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Annotations as Flow;

/**
 * The intra dimensional fallback graph domain model
 * Represents the generalization/specialization mechanism within each content subgraph dimension
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
        $this->dimensions = [];
        $this->prioritizedContentDimensions = [];

        foreach ($this->contentDimensionPresetSource->getAllPresets() as $dimensionName => $dimensionConfiguration) {
            $presetDimension = $this->createDimension($dimensionName, $dimensionConfiguration['label'] ?? null);
            $valueNodes = [];
            $generalizationEdges = [];
            foreach ($dimensionConfiguration['presets'] as $valueName => $valueConfiguration) {
                if (!isset($valueConfiguration['values'])) {
                    continue;
                }
                $parent = null;
                $values = array_reverse($valueConfiguration['values']);
                foreach ($values as $dimensionValue) {
                    $valueNodes[$dimensionValue] = $dimensionValue;
                    if ($parent) {
                        $generalizationEdges[$dimensionValue] = $parent;
                    }
                    $parent = $dimensionValue;
                }
            }
            $rootValues = array_diff_key($valueNodes, $generalizationEdges);

            $specializationEdges = [];
            foreach ($generalizationEdges as $specialization => $generalization) {
                $specializationEdges[$generalization][] = $specialization;
            }

            foreach ($rootValues as $rootValue) {
                $this->traverseSpecializationEdges($presetDimension, $specializationEdges, $rootValue);
            }

            $this->prioritizedContentDimensions[] = $presetDimension;
        }
    }

    /**
     * @param ContentDimension $presetDimension
     * @param array $specializationEdges
     * @param string $value
     * @param Dimension\Model\ContentDimensionValue|null $parentValue
     */
    protected function traverseSpecializationEdges(ContentDimension $presetDimension, array $specializationEdges, string $value, Dimension\Model\ContentDimensionValue $parentValue = null)
    {
        $currentValue = $presetDimension->createValue($value, $parentValue);
        if (isset($specializationEdges[$value])) {
            foreach ($specializationEdges[$value] as $specializedValue) {
                $this->traverseSpecializationEdges($presetDimension, $specializationEdges, $specializedValue, $currentValue);
            }
        }
    }

    /**
     * @param string $dimensionName
     * @param string|null $label
     * @return ContentDimension
     */
    protected function createDimension(string $dimensionName, string $label = null): ContentDimension
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
    public function getDimension(string $dimensionName): ?ContentDimension
    {
        return $this->dimensions[$dimensionName] ?? null;
    }

    /**
     * @return array|ContentDimension[]
     */
    public function getPrioritizedContentDimensions(): array
    {
        return $this->prioritizedContentDimensions;
    }
}
