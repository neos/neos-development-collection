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

namespace Neos\ContentRepository\DimensionSpace;

use Neos\ContentRepository\Dimension;

/**
 * The inter dimensional variation graph domain model
 * Represents the specialization and generalization mechanism between dimension space points
 * @api
 */
class InterDimensionalVariationGraph
{
    /**
     * Weighed dimension space points, indexed by identity (DSP) hash
     *
     * @var array<string,WeightedDimensionSpacePoint>|null
     */
    protected ?array $weightedDimensionSpacePoints = null;

    /**
     * Generalization dimension space point sets, indexed by specialization hash
     *
     * @var array<string,DimensionSpacePointSet>|null
     */
    protected ?array $indexedGeneralizations = null;

    /**
     * Specialization dimension space point sets, indexed by generalization hash
     *
     * @var array<string,DimensionSpacePointSet>|null
     */
    protected ?array $indexedSpecializations = null;

    /**
     * Weighed generalizations, indexed by specialization hash and relative weight
     *
     * @var array<string,array<int,DimensionSpacePoint>>|null
     */
    protected ?array $weightedGeneralizations = null;

    /**
     * Weighed specializations, indexed by generalization hash, relative weight and specialization hash
     * @var array<string,array<int,array<string,DimensionSpacePoint>>>|null
     */
    protected ?array $weightedSpecializations = null;

    /**
     * Primary generalization dimension space points, indexed by specialization hash
     *
     * @var array<string,DimensionSpacePoint>
     */
    protected ?array $primaryGeneralizations = null;

    protected ?int $weightNormalizationBase = null;

    public function __construct(
        private Dimension\ContentDimensionSourceInterface $contentDimensionSource,
        private ContentDimensionZookeeper $contentDimensionZookeeper
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
        if (is_null($this->weightedDimensionSpacePoints)) {
            $this->initializeWeightedDimensionSpacePoints();
        }
        /** @var array<string,WeightedDimensionSpacePoint> $weighedDimensionSpacePoints */
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
        if (is_null($this->weightedDimensionSpacePoints)) {
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
        if (is_null($this->weightNormalizationBase)) {
            $base = 0;
            foreach ($this->contentDimensionSource->getContentDimensionsOrderedByPriority() as $contentDimension) {
                $base = max($base, $contentDimension->getMaximumDepth()->depth + 1);
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

        $indexedGeneralizations = [];
        $indexedSpecializations = [];

        foreach ($this->getWeightedDimensionSpacePoints() as $generalizationHash => $generalization) {
            if (!isset($normalizedVariationWeights[$generalizationHash])) {
                $normalizedVariationWeights[$generalizationHash]
                    = $generalization->weight->normalize($this->determineWeightNormalizationBase());
            }

            foreach ($generalization->dimensionValues as $rawDimensionIdentifier => $contentDimensionValue) {
                $dimensionIdentifier = new Dimension\ContentDimensionIdentifier($rawDimensionIdentifier);
                /** @var Dimension\ContentDimension $dimension */
                $dimension = $this->contentDimensionSource->getDimension($dimensionIdentifier);
                foreach ($dimension->getSpecializations($contentDimensionValue) as $specializedValue) {
                    $specializedDimensionSpacePoint = $generalization->dimensionSpacePoint
                        ->vary($dimensionIdentifier, (string)$specializedValue);
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

        /** @var array<string,array<string,DimensionSpacePoint>> $indexedGeneralizations */
        foreach ($indexedGeneralizations as $specializationHash => $generalizations) {
            $this->indexedGeneralizations[$specializationHash] = new DimensionSpacePointSet($generalizations);
        }
        /** @var array<string,array<string,DimensionSpacePoint>> $indexedSpecializations */
        foreach ($indexedSpecializations as $generalizationHash => $specializations) {
            $this->indexedSpecializations[$generalizationHash] = new DimensionSpacePointSet($specializations);
        }

        /** @phpstan-ignore-next-line */
        foreach ($this->weightedGeneralizations as $specializationHash => &$generalizationsByWeight) {
            ksort($generalizationsByWeight);
        }
    }

    /**
     * @param array<string,int> $normalizedVariationWeights
     * @param array<string,array<string,DimensionSpacePoint>>& $indexedGeneralizations
     * @param array<string,array<string,DimensionSpacePoint>>& $indexedSpecializations
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
                /** @var WeightedDimensionSpacePoint $weighedParent */
                $weighedParent = $this->getWeightedDimensionSpacePointByHash($parentGeneralizationHash);
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
        if (is_null($this->indexedSpecializations)) {
            $this->initializeVariations();
        }

        return $this->indexedSpecializations[$generalization->hash] ?? new DimensionSpacePointSet([]);
    }

    /**
     * Returns generalizations of a dimension space point
     */
    public function getIndexedGeneralizations(DimensionSpacePoint $specialization): DimensionSpacePointSet
    {
        if (is_null($this->indexedGeneralizations)) {
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
        if (is_null($this->weightedSpecializations)) {
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
        if (is_null($this->weightedGeneralizations)) {
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
        if (is_null($this->primaryGeneralizations)) {
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

        if (is_null($this->indexedGeneralizations)) {
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
