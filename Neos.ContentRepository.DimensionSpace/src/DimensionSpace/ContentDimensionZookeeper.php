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

namespace Neos\ContentRepository\DimensionSpace\DimensionSpace;

use Neos\ContentRepository\DimensionSpace\Dimension;

/**
 * Implementation detail of the content dimension mechanism.
 *
 * It reads the Content Dimension Source and figures out which dimension space combinations are valid; so
 * it calculates the Allowed Dimension Subspace.
 *
 * @internal Use {@see InterDimensionalVariationGraph instead}
 */
final class ContentDimensionZookeeper
{
    /**
     * Needs to stay protected as long as we need to be able to reset it via ObjectAccess
     * @var array<int,array<string,Dimension\ContentDimensionValue>>
     */
    protected ?array $allowedCombinations = null;

    public function __construct(
        private Dimension\ContentDimensionSourceInterface $contentDimensionSource
    ) {
    }

    protected function initializeAllowedCombinations(): void
    {
        $orderedDimensions = $this->contentDimensionSource->getContentDimensionsOrderedByPriority();
        if (!empty($orderedDimensions)) {
            /** @var array<int,array<string,Dimension\ContentDimensionValue>> $dimensionCombinations */
            $dimensionCombinations = [];
            foreach ($this->contentDimensionSource->getContentDimensionsOrderedByPriority() as $contentDimension) {
                if (empty($dimensionCombinations)) {
                    foreach ($contentDimension->values as $serializedValue => $dimensionValue) {
                        $dimensionCombinations[] = [(string)$contentDimension->identifier => $dimensionValue];
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
     * @param array<int,array<string,Dimension\ContentDimensionValue>> $dimensionCombinations
     */
    protected function extendCombinationsWithDimension(
        array &$dimensionCombinations,
        Dimension\ContentDimension $contentDimension
    ): void {
        $currentDimensionCombinations = [];
        foreach ($dimensionCombinations as $dimensionCombination) {
            foreach ($contentDimension->values as $currentDimensionValue) {
                foreach ($dimensionCombination as $otherDimensionIdentifier => $otherDimensionValue) {
                    if (
                        !$currentDimensionValue->canBeCombinedWith(
                            new Dimension\ContentDimensionIdentifier($otherDimensionIdentifier),
                            $otherDimensionValue
                        )
                        || !$otherDimensionValue->canBeCombinedWith(
                            $contentDimension->identifier,
                            $currentDimensionValue
                        )
                    ) {
                        continue 2;
                    }
                }
                $newDimensionCombination = $dimensionCombination;
                $newDimensionCombination[(string)$contentDimension->identifier] = $currentDimensionValue;
                $currentDimensionCombinations[] = $newDimensionCombination;
            }
        }

        $dimensionCombinations = $currentDimensionCombinations;
    }

    /**
     * TODO refactor to private/protected
     * @return array<int,array<string,Dimension\ContentDimensionValue>>
     */
    public function getAllowedCombinations(): array
    {
        if (is_null($this->allowedCombinations)) {
            $this->initializeAllowedCombinations();
        }
        /** @var array<int,array<string,Dimension\ContentDimensionValue>> $allowedCombinations */
        $allowedCombinations = $this->allowedCombinations;

        return $allowedCombinations;
    }

    /**
     * @internal use {@see InterDimensionalVariationGraph::getDimensionSpacePoints()} instead.
     */
    public function getAllowedDimensionSubspace(): DimensionSpacePointSet
    {
        $points = [];

        foreach ($this->getAllowedCombinations() as $dimensionCombination) {
            $coordinates = [];
            foreach ($dimensionCombination as $contentDimensionIdentifier => $contentDimensionValue) {
                $coordinates[$contentDimensionIdentifier] = (string)$contentDimensionValue;
            }

            $point = DimensionSpacePoint::fromArray($coordinates);
            $points[$point->hash] = $point;
        }

        return new DimensionSpacePointSet($points);
    }
}
