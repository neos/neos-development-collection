<?php

namespace Neos\ContentRepository\DimensionSpace\Dimension;

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The content dimension zookeeper
 */
final class ContentDimensionZookeeper
{
    /**
     * @var ContentDimensionSourceInterface
     */
    protected $contentDimensionSource;

    /**
     * @var array|ContentDimensionValue[][]
     */
    protected $allowedCombinations;

    public function __construct(ContentDimensionSourceInterface $contentDimensionSource)
    {
        $this->contentDimensionSource = $contentDimensionSource;
    }

    /**
     * @return void
     */
    protected function initializeAllowedCombinations()
    {
        if (!empty($this->contentDimensionSource->getContentDimensionsOrderedByPriority())) {
            /** @var ContentDimensionValue[][] $dimensionCombinations */
            $dimensionCombinations = [];
            foreach ($this->contentDimensionSource->getContentDimensionsOrderedByPriority() as $contentDimension) {
                if (empty($dimensionCombinations)) {
                    foreach ($contentDimension->getValues() as $serializedValue => $dimensionValue) {
                        $dimensionCombinations[] = [(string)$contentDimension->getIdentifier() => $dimensionValue];
                    }
                } else {
                    $this->extendCombinationsWithDimension($dimensionCombinations, $contentDimension);
                }
            }
            $this->allowedCombinations = $dimensionCombinations;
        } else {
            $this->allowedCombinations = [[]];
        }
    }

    /**
     * @param array|ContentDimensionValue[][] $dimensionCombinations
     * @param ContentDimension $contentDimension
     */
    protected function extendCombinationsWithDimension(
        array & $dimensionCombinations,
        ContentDimension $contentDimension
    ): void {
        $currentDimensionCombinations = [];
        foreach ($dimensionCombinations as $dimensionCombination) {
            foreach ($contentDimension->getValues() as $currentDimensionValue) {
                foreach ($dimensionCombination as $otherDimensionIdentifier => $otherDimensionValue) {
                    if (!$currentDimensionValue->canBeCombinedWith(new ContentDimensionIdentifier($otherDimensionIdentifier),
                            $otherDimensionValue)
                        || !$otherDimensionValue->canBeCombinedWith($contentDimension->getIdentifier(),
                            $currentDimensionValue)) {
                        continue 2;
                    }
                }
                $newDimensionCombination = $dimensionCombination;
                $newDimensionCombination[(string)$contentDimension->getIdentifier()] = $currentDimensionValue;
                $currentDimensionCombinations[] = $newDimensionCombination;
            }
        }

        $dimensionCombinations = $currentDimensionCombinations;
    }

    /**
     * @return array|ContentDimensionValue[][]
     */
    public function getAllowedCombinations(): array
    {
        if (is_null($this->allowedCombinations)) {
            $this->initializeAllowedCombinations();
        }

        return $this->allowedCombinations;
    }
}
