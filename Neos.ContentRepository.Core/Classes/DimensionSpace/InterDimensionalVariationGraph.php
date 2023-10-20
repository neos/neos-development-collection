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
 * The interdimensional variation graph domain model
 * Represents the specialization and generalization mechanism between dimension space points
 * @api
 */
class InterDimensionalVariationGraph
{
    /**
     * Weighed dimension space points, indexed by identity (DSP) hash
     *
     * @var array<string,WeightedDimensionSpacePoint>
     */
    protected array $weightedDimensionSpacePoints;

    /**
     * Generalization dimension space point sets, indexed by specialization hash
     *
     * @var array<string,DimensionSpacePointSet>
     */
    protected array $indexedGeneralizations;

    /**
     * Specialization dimension space point sets, indexed by generalization hash
     *
     * @var array<string,DimensionSpacePointSet>
     */
    protected array $indexedSpecializations;

    /**
     * Weighed generalizations, indexed by specialization hash and relative weight
     *
     * @var array<string,array<int,DimensionSpacePoint>>
     */
    protected array $weightedGeneralizations;

    /**
     * Weighed specializations, indexed by generalization hash, relative weight and specialization hash
     * @var array<string,array<int,array<string,DimensionSpacePoint>>>
     */
    protected array $weightedSpecializations;

    /**
     * Primary generalization dimension space points, indexed by specialization hash
     *
     * @var array<string,DimensionSpacePoint>
     */
    protected array $primaryGeneralizations;

    protected int $weightNormalizationBase;

    public function __construct(
        private readonly Dimension\ContentDimensionSourceInterface $contentDimensionSource,
        private readonly ContentDimensionZookeeper $contentDimensionZookeeper
    ) {
    }

    protected function initializeWeightedDimensionSpacePoints(): void
    {
        $this->weightedDimensionSpacePoints = [];
        foreach ($this->contentDimensionZookeeper->getAllowedCombinations() as $dimensionValues) {
            $weightedDimensionSpacePoint = new WeightedDimensionSpacePoint($dimensionValues);
            $this->weightedDimensionSpacePoints[$weightedDimensionSpacePoint->getIdentityHash()]
                = $weightedDimensionSpacePoint;
        }
    }

    public function getDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->contentDimensionZookeeper->getAllowedDimensionSubspace();
    }

    /**
     * @return array<string,WeightedDimensionSpacePoint>
     * @api
     */
    public function getWeightedDimensionSpacePoints(): array
    {
        if (!isset($this->weightedDimensionSpacePoints)) {
            $this->initializeWeightedDimensionSpacePoints();
        }
        $weighedDimensionSpacePoints = $this->weightedDimensionSpacePoints;

        return $weighedDimensionSpacePoints;
    }

    public function getWeightedDimensionSpacePointByDimensionSpacePoint(
        DimensionSpacePoint $point
    ): ?WeightedDimensionSpacePoint {
        return $this->getWeightedDimensionSpacePointByHash($point->hash);
    }

    public function getWeightedDimensionSpacePointByHash(string $hash): ?WeightedDimensionSpacePoint
    {
        if (!isset($this->weightedDimensionSpacePoints)) {
            $this->initializeWeightedDimensionSpacePoints();
        }

        return $this->weightedDimensionSpacePoints[$hash] ?? null;
    }

    /**
     * Returns the root generalizations indexed by hash
     *
     * @return array<string,DimensionSpacePoint>
     */
    public function getRootGeneralizations(): array
    {
        $rootGeneralizations = [];
        foreach ($this->getWeightedDimensionSpacePoints() as $dimensionSpacePointHash => $weightedDimensionSpacePoint) {
            if ($this->getIndexedGeneralizations($weightedDimensionSpacePoint->dimensionSpacePoint)->isEmpty()) {
                $rootGeneralizations[$dimensionSpacePointHash] = $weightedDimensionSpacePoint->dimensionSpacePoint;
            }
        }

        return $rootGeneralizations;
    }

    protected function determineWeightNormalizationBase(): int
    {
        if (!isset($this->weightNormalizationBase)) {
            $base = 0;
            foreach ($this->contentDimensionSource->getContentDimensionsOrderedByPriority() as $contentDimension) {
                $base = max($base, $contentDimension->getMaximumDepth()->value + 1);
            }

            $this->weightNormalizationBase = $base;
        }

        return $this->weightNormalizationBase;
    }

    protected function initializeVariations(): void
    {
        $normalizedVariationWeights = [];
        $lowestVariationWeights = [];
        $this->weightedGeneralizations = [];

        /** @var array<string,array<string,DimensionSpacePoint>> $indexedGeneralizations */
        $indexedGeneralizations = [];
        /** @var array<string,array<string,DimensionSpacePoint>> $indexedSpecializations */
        $indexedSpecializations = [];

        foreach ($this->getWeightedDimensionSpacePoints() as $generalizationHash => $generalization) {
            if (!isset($normalizedVariationWeights[$generalizationHash])) {
                $normalizedVariationWeights[$generalizationHash]
                    = $generalization->weight->normalize($this->determineWeightNormalizationBase());
            }

            foreach ($generalization->dimensionValues as $rawDimensionId => $contentDimensionValue) {
                $dimensionId = new Dimension\ContentDimensionId($rawDimensionId);
                $dimension = $this->contentDimensionSource->getDimension($dimensionId);
                assert($dimension !== null);
                foreach ($dimension->getSpecializations($contentDimensionValue) as $specializedValue) {
                    $specializedDimensionSpacePoint = $generalization->dimensionSpacePoint
                        ->vary($dimensionId, $specializedValue->value);
                    if (
                        !$this->contentDimensionZookeeper->getAllowedDimensionSubspace()
                            ->contains($specializedDimensionSpacePoint)
                    ) {
                        continue;
                    }
                    /** @var WeightedDimensionSpacePoint $specialization */
                    $specialization = $this->getWeightedDimensionSpacePointByDimensionSpacePoint(
                        $specializedDimensionSpacePoint
                    );

                    if (!isset($normalizedVariationWeights[$specialization->getIdentityHash()])) {
                        $normalizedVariationWeights[$specialization->getIdentityHash()]
                            = $specialization->weight->normalize($this->determineWeightNormalizationBase());
                    }
                    $this->initializeVariationsForDimensionSpacePointPair(
                        $specialization,
                        $generalization,
                        $normalizedVariationWeights,
                        $indexedGeneralizations,
                        $indexedSpecializations
                    );
                    $normalizedVariationWeight = $normalizedVariationWeights[$specialization->getIdentityHash()]
                        - $normalizedVariationWeights[$generalizationHash];
                    if (
                        !isset($lowestVariationWeights[$specialization->getIdentityHash()])
                        || $normalizedVariationWeight < $lowestVariationWeights[$specialization->getIdentityHash()]
                    ) {
                        $this->primaryGeneralizations[$specialization->getIdentityHash()]
                            = $generalization->dimensionSpacePoint;
                        $lowestVariationWeights[$specialization->getIdentityHash()] = $normalizedVariationWeight;
                    }
                }
            }
        }

        foreach ($indexedGeneralizations as $specializationHash => $generalizations) {
            $this->indexedGeneralizations[$specializationHash] = new DimensionSpacePointSet($generalizations);
        }
        foreach ($indexedSpecializations as $generalizationHash => $specializations) {
            $this->indexedSpecializations[$generalizationHash] = new DimensionSpacePointSet($specializations);
        }

        foreach ($this->weightedGeneralizations as $specializationHash => &$generalizationsByWeight) {
            ksort($generalizationsByWeight);
        }
    }

    /**
     * @param array<string,int> $normalizedVariationWeights
     * @param array<string,array<string,DimensionSpacePoint>>& $indexedGeneralizations
     * @param array<string,array<string,DimensionSpacePoint>>& $indexedSpecializations
     * @param-out array<string,array<string,DimensionSpacePoint>> $indexedGeneralizations
     * @param-out array<string,array<string,DimensionSpacePoint>> $indexedSpecializations
     */
    protected function initializeVariationsForDimensionSpacePointPair(
        WeightedDimensionSpacePoint $specialization,
        WeightedDimensionSpacePoint $generalization,
        array $normalizedVariationWeights,
        array &$indexedGeneralizations,
        array &$indexedSpecializations
    ): void {
        $generalizationsToProcess = [$generalization->getIdentityHash() => $generalization];
        if (isset($indexedGeneralizations[$generalization->getIdentityHash()])) {
            $generalizations = $indexedGeneralizations[$generalization->getIdentityHash()];
            foreach ($generalizations as $parentGeneralizationHash => $parentGeneralization) {
                $weighedParent = $this->getWeightedDimensionSpacePointByHash($parentGeneralizationHash);
                assert($weighedParent !== null);
                $generalizationsToProcess[$parentGeneralizationHash] = $weighedParent;
            }
        }

        foreach ($generalizationsToProcess as $generalizationHashToProcess => $generalizationToProcess) {
            $normalizedWeightDifference = abs(
                $normalizedVariationWeights[$generalizationHashToProcess]
                    - $normalizedVariationWeights[$specialization->getIdentityHash()]
            );
            $indexedGeneralizations[$specialization->getIdentityHash()]
                [$generalizationToProcess->getIdentityHash()] = $generalizationToProcess->dimensionSpacePoint;
            $this->weightedGeneralizations[$specialization->getIdentityHash()]
                [$normalizedWeightDifference] = $generalizationToProcess->dimensionSpacePoint;

            $indexedSpecializations[$generalizationToProcess->getIdentityHash()]
                [$specialization->getIdentityHash()] = $specialization->dimensionSpacePoint;
            $this->weightedSpecializations[$generalizationToProcess->getIdentityHash()][$normalizedWeightDifference]
            [$specialization->getIdentityHash()] = $specialization->dimensionSpacePoint;
        }
    }

    /**
     * Returns specializations of a dimension space point
     */
    public function getIndexedSpecializations(DimensionSpacePoint $generalization): DimensionSpacePointSet
    {
        if (!isset($this->indexedSpecializations)) {
            $this->initializeVariations();
        }

        return $this->indexedSpecializations[$generalization->hash] ?? new DimensionSpacePointSet([]);
    }

    /**
     * Returns generalizations of a dimension space point
     */
    public function getIndexedGeneralizations(DimensionSpacePoint $specialization): DimensionSpacePointSet
    {
        if (!isset($this->indexedGeneralizations)) {
            $this->initializeVariations();
        }

        return $this->indexedGeneralizations[$specialization->hash] ?? new DimensionSpacePointSet([]);
    }

    /**
     * Returns specializations of a dimension space point indexed by relative weight and specialization hash
     *
     * @return array<int,array<string,DimensionSpacePoint>>
     */
    public function getWeightedSpecializations(DimensionSpacePoint $generalization): array
    {
        if (!isset($this->weightedSpecializations)) {
            $this->initializeVariations();
        }

        return $this->weightedSpecializations[$generalization->hash] ?? [];
    }

    /**
     * Returns generalizations of a dimension space point indexed by relative weight
     *
     * @return array<int,DimensionSpacePoint>
     */
    public function getWeightedGeneralizations(DimensionSpacePoint $specialization): array
    {
        if (!isset($this->weightedGeneralizations)) {
            $this->initializeVariations();
        }

        return $this->weightedGeneralizations[$specialization->hash] ?? [];
    }

    /**
     * @api
     * @throws Exception\DimensionSpacePointNotFound
     */
    public function getSpecializationSet(
        DimensionSpacePoint $origin,
        bool $includeOrigin = true,
        DimensionSpacePointSet $excludedSet = null
    ): DimensionSpacePointSet {
        if (!$this->contentDimensionZookeeper->getAllowedDimensionSubspace()->contains($origin)) {
            throw Exception\DimensionSpacePointNotFound::becauseItIsNotWithinTheAllowedDimensionSubspace($origin);
        } else {
            $specializations = [];
            if ($includeOrigin) {
                $specializations[$origin->hash] = $origin;
            }

            foreach ($this->getIndexedSpecializations($origin) as $specialization) {
                if (!$excludedSet || !$excludedSet->contains($specialization)) {
                    $specializations[$specialization->hash] = $specialization;
                }
            }

            return new DimensionSpacePointSet($specializations);
        }
    }

    /**
     * @api
     */
    public function getPrimaryGeneralization(DimensionSpacePoint $specialization): ?DimensionSpacePoint
    {
        if (!isset($this->primaryGeneralizations)) {
            $this->initializeVariations();
        }

        return $this->primaryGeneralizations[$specialization->hash] ?? null;
    }

    /**
     * @api
     */
    public function getVariantType(DimensionSpacePoint $subject, DimensionSpacePoint $object): VariantType
    {
        if ($subject->equals($object)) {
            return VariantType::TYPE_SAME;
        }

        if (!isset($this->indexedGeneralizations)) {
            $this->initializeVariations();
        }

        if (
            isset($this->indexedGeneralizations[$object->hash])
            && $this->indexedGeneralizations[$object->hash]->contains($subject)
        ) {
            return VariantType::TYPE_GENERALIZATION;
        }
        if (
            isset($this->indexedSpecializations[$object->hash])
            && $this->indexedSpecializations[$object->hash]->contains($subject)
        ) {
            return VariantType::TYPE_SPECIALIZATION;
        }

        return VariantType::TYPE_PEER;
    }
}
