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
use Neos\ContentRepository\Exception\DimensionSpacePointNotFound;
use Neos\Flow\Annotations as Flow;

/**
 * The inter dimensional variation graph domain model
 * Represents the specialization and generalization mechanism between content subgraphs
 *
 * @Flow\Scope("singleton")
 */
class InterDimensionalVariationGraph
{
    /**
     * @Flow\Inject
     * @var Dimension\ContentDimensionZookeeper
     */
    protected $contentDimensionZookeeper;

    /**
     * @Flow\Inject
     * @var AllowedDimensionSubspace
     */
    protected $allowedDimensionSubspace;

    /**
     * @Flow\Inject
     * @var Dimension\ContentDimensionSourceInterface
     */
    protected $contentDimensionSource;

    /**
     * @var array|ContentSubgraph[]
     */
    protected $subgraphs;

    /**
     * @var array|ContentSubgraph[][]
     */
    protected $generalizations;

    /**
     * @var array|ContentSubgraph[][]
     */
    protected $specializations;

    /**
     * @var array|ContentSubgraph[]
     */
    protected $primaryGeneralizations;

    /**
     * @var int
     */
    protected $weightNormalizationBase;


    /**
     * @return void
     */
    protected function initializeSubgraphs()
    {
        $this->subgraphs = [];
        foreach ($this->contentDimensionZookeeper->getAllowedCombinations() as $dimensionValues) {
            $subgraph = new ContentSubgraph($dimensionValues);
            $this->subgraphs[$subgraph->getIdentityHash()] = $subgraph;
        }
    }

    /**
     * @return array|ContentSubgraph[]
     * @api
     */
    public function getSubgraphs(): array
    {
        if (is_null($this->subgraphs)) {
            $this->initializeSubgraphs();
        }

        return $this->subgraphs;
    }

    /**
     * @param Domain\ValueObject\DimensionSpacePoint $point
     * @return ContentSubgraph|null
     */
    public function getSubgraphByDimensionSpacePoint(Domain\ValueObject\DimensionSpacePoint $point): ?ContentSubgraph
    {
        return $this->getSubgraphByDimensionSpacePointHash($point->getHash());
    }

    /**
     * @param string $hash
     * @return ContentSubgraph|null
     */
    public function getSubgraphByDimensionSpacePointHash(string $hash): ?ContentSubgraph
    {
        if (is_null($this->subgraphs)) {
            $this->initializeSubgraphs();
        }

        return isset($this->subgraphs[$hash]) ? $this->subgraphs[$hash] : null;
    }

    /**
     * @return int
     */
    protected function determineWeightNormalizationBase(): int
    {
        if (is_null($this->weightNormalizationBase)) {
            $base = 0;
            foreach ($this->contentDimensionSource->getContentDimensionsOrderedByPriority() as $contentDimension) {
                $base = max($base, $contentDimension->getMaximumDepth()->getDepth() + 1);
            }

            $this->weightNormalizationBase = $base;
        }

        return $this->weightNormalizationBase;
    }

    /**
     * @param ContentSubgraphVariationWeight $weight
     * @return int
     */
    protected function normalizeWeight(ContentSubgraphVariationWeight $weight): int
    {
        $base = $this->determineWeightNormalizationBase();
        $normalizedWeight = 0;
        $exponent = count($weight) - 1;
        foreach ($weight->getWeight() as $dimensionIdentifier => $specializationDepth) {
            $normalizedWeight += pow($base, $exponent) * $specializationDepth->getDepth();
            $exponent--;
        }

        return $normalizedWeight;
    }

    /**
     * @return void
     */
    protected function initializeVariations()
    {
        $normalizedVariationWeights = [];
        $lowestVariationWeights = [];

        foreach ($this->getSubgraphs() as $generalizationHash => $generalization) {
            if (!isset($normalizedVariationWeights[$generalizationHash])) {
                $normalizedVariationWeights[$generalizationHash] = $this->normalizeWeight($generalization->getWeight());
            }

            foreach ($generalization->getDimensionValues() as $rawDimensionIdentifier => $contentDimensionValue) {
                $dimensionIdentifier = new Dimension\ContentDimensionIdentifier($rawDimensionIdentifier);
                $dimension = $this->contentDimensionSource->getDimension($dimensionIdentifier);
                foreach ($dimension->getSpecializations($contentDimensionValue) as $specializedValue) {
                    $specializedDimensionSpacePoint = $generalization->getDimensionSpacePoint()->vary($dimensionIdentifier, $specializedValue);
                    if (!$this->allowedDimensionSubspace->contains($specializedDimensionSpacePoint)) {
                        continue;
                    }
                    $specialization = $this->getSubgraphByDimensionSpacePoint($specializedDimensionSpacePoint);

                    $this->initializeVariationsForSubgraphPair($specialization, $generalization);

                    if (!isset($normalizedVariationWeights[$specialization->getIdentityHash()])) {
                        $normalizedVariationWeights[$specialization->getIdentityHash()] = $this->normalizeWeight($specialization->getWeight());
                    }
                    $normalizedVariationWeight = $normalizedVariationWeights[$specialization->getIdentityHash()] - $normalizedVariationWeights[$generalizationHash];
                    if (!isset($lowestVariationWeights[$specialization->getIdentityHash()]) || $normalizedVariationWeight < $lowestVariationWeights[$specialization->getIdentityHash()]) {
                        $this->primaryGeneralizations[$specialization->getIdentityHash()] = $generalization;
                    }
                }
            }
        }
    }

    /**
     * @param ContentSubgraph $specialization
     * @param ContentSubgraph $generalization
     */
    protected function initializeVariationsForSubgraphPair(ContentSubgraph $specialization, ContentSubgraph $generalization)
    {
        /** @var array|ContentSubgraph[] $generalizationsToProcess */
        $generalizationsToProcess = [$generalization->getIdentityHash() => $generalization];
        if (isset($this->generalizations[$generalization->getIdentityHash()])) {
            foreach ($this->generalizations[$generalization->getIdentityHash()] as $parentGeneralizationHash => $parentGeneralization) {
                $generalizationsToProcess[$parentGeneralizationHash] = $parentGeneralization;
            }
        }

        foreach ($generalizationsToProcess as $generalizationHashToProcess => $generalizationToProcess) {
            $this->generalizations[$specialization->getIdentityHash()][$generalizationToProcess->getIdentityHash()] = $generalizationToProcess;
            $this->specializations[$generalizationToProcess->getIdentityHash()][$specialization->getIdentityHash()] = $specialization;
        }
    }

    /**
     * @param ContentSubgraph $generalization
     * @return array|ContentSubgraph[]
     */
    public function getSpecializations(ContentSubgraph $generalization): array
    {
        if (is_null($this->specializations)) {
            $this->initializeVariations();
        }

        $specializations = [];
        if (isset($this->specializations[$generalization->getIdentityHash()])) {
            foreach ($this->specializations[$generalization->getIdentityHash()] as $specialization) {
                $specializations[$specialization->getIdentityHash()] = $specialization;
            }
        }

        return $specializations;
    }

    /**
     * @param ContentSubgraph $specialization
     * @return array|ContentSubgraph[]
     */
    public function getGeneralizations(ContentSubgraph $specialization): array
    {
        if (is_null($this->generalizations)) {
            $this->initializeVariations();
        }

        $generalizations = [];
        if (isset($this->generalizations[$specialization->getIdentityHash()])) {
            foreach ($this->generalizations[$specialization->getIdentityHash()] as $generalization) {
                $generalizations[$generalization->getIdentityHash()] = $generalization;
            }
        }

        return $generalizations;
    }

    /**
     * @param Domain\ValueObject\DimensionSpacePoint $origin
     * @param bool $includeOrigin
     * @param Domain\ValueObject\DimensionSpacePointSet|null $excludedSet
     * @return Domain\ValueObject\DimensionSpacePointSet
     * @throws DimensionSpacePointNotFound
     */
    public function getSpecializationSet(
        Domain\ValueObject\DimensionSpacePoint $origin,
        bool $includeOrigin = true,
        Domain\ValueObject\DimensionSpacePointSet $excludedSet = null
    ): Domain\ValueObject\DimensionSpacePointSet {
        if (!$this->allowedDimensionSubspace->contains($origin)) {
            throw new DimensionSpacePointNotFound(sprintf('%s was not found in the allowed dimension subspace', $origin), 1505929456);
        } else {
            $subgraph = $this->getSubgraphByDimensionSpacePointHash($origin->getHash());

            if (is_null($this->specializations)) {
                $this->initializeVariations();
            }

            $specializations = [];
            if ($includeOrigin) {
                $specializations[$origin->getHash()] = $origin;
            }
            if (isset($this->specializations[$subgraph->getIdentityHash()])) {
                foreach ($this->specializations[$subgraph->getIdentityHash()] as $specialization) {
                    if (!$excludedSet || !$excludedSet->contains($specialization->getDimensionSpacePoint())) {
                        $specializations[$specialization->getDimensionSpacePoint()->getHash()] = $specialization->getDimensionSpacePoint();
                    }
                }
            }

            return new Domain\ValueObject\DimensionSpacePointSet($specializations);
        }
    }

    /**
     * @param ContentSubgraph $contentSubgraph
     * @return ContentSubgraph|null
     * @api
     */
    public function getPrimaryGeneralization(ContentSubgraph $contentSubgraph): ?ContentSubgraph
    {
        if (is_null($this->generalizations)) {
            $this->initializeVariations();
        }

        return $this->primaryGeneralizations[$contentSubgraph->getIdentityHash()] ?? null;
    }
}
