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
 * Represents the specialization and generalization mechanism between dimension space points
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
     * @var array|WeightedDimensionSpacePoint[]
     */
    protected $weightedDimensionSpacePoints;

    /**
     * @var array|Domain\ValueObject\DimensionSpacePoint[][]
     */
    protected $indexedGeneralizations;

    /**
     * @var array|Domain\ValueObject\DimensionSpacePoint[][]
     */
    protected $indexedSpecializations;

    /**
     * @var array|Domain\ValueObject\DimensionSpacePoint[][]
     */
    protected $weightedGeneralizations;

    /**
     * @var array|Domain\ValueObject\DimensionSpacePoint[][]
     */
    protected $weightedSpecializations;

    /**
     * @var array|Domain\ValueObject\DimensionSpacePoint[]
     */
    protected $primaryGeneralizations;

    /**
     * @var int
     */
    protected $weightNormalizationBase;


    /**
     * @return void
     */
    protected function initializeWeightedDimensionSpacePoints()
    {
        $this->weightedDimensionSpacePoints = [];
        foreach ($this->contentDimensionZookeeper->getAllowedCombinations() as $dimensionValues) {
            $subgraph = new WeightedDimensionSpacePoint($dimensionValues);
            $this->weightedDimensionSpacePoints[$subgraph->getIdentityHash()] = $subgraph;
        }
    }

    /**
     * @return array|WeightedDimensionSpacePoint[]
     * @api
     */
    public function getWeightedDimensionSpacePoints(): array
    {
        if (is_null($this->weightedDimensionSpacePoints)) {
            $this->initializeWeightedDimensionSpacePoints();
        }

        return $this->weightedDimensionSpacePoints;
    }

    /**
     * @param Domain\ValueObject\DimensionSpacePoint $point
     * @return WeightedDimensionSpacePoint|null
     */
    public function getWeightedDimensionSpacePointByDimensionSpacePoint(Domain\ValueObject\DimensionSpacePoint $point): ?WeightedDimensionSpacePoint
    {
        return $this->getWeightedDimensionSpacePointByHash($point->getHash());
    }

    /**
     * @param string $hash
     * @return WeightedDimensionSpacePoint|null
     */
    public function getWeightedDimensionSpacePointByHash(string $hash): ?WeightedDimensionSpacePoint
    {
        if (is_null($this->weightedDimensionSpacePoints)) {
            $this->initializeWeightedDimensionSpacePoints();
        }

        return isset($this->weightedDimensionSpacePoints[$hash]) ? $this->weightedDimensionSpacePoints[$hash] : null;
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
     * @return void
     */
    protected function initializeVariations()
    {
        $normalizedVariationWeights = [];
        $lowestVariationWeights = [];

        foreach ($this->getWeightedDimensionSpacePoints() as $generalizationHash => $generalization) {
            if (!isset($normalizedVariationWeights[$generalizationHash])) {
                $normalizedVariationWeights[$generalizationHash] = $generalization->getWeight()->normalize($this->determineWeightNormalizationBase());
            }

            foreach ($generalization->getDimensionValues() as $rawDimensionIdentifier => $contentDimensionValue) {
                $dimensionIdentifier = new Dimension\ContentDimensionIdentifier($rawDimensionIdentifier);
                $dimension = $this->contentDimensionSource->getDimension($dimensionIdentifier);
                foreach ($dimension->getSpecializations($contentDimensionValue) as $specializedValue) {
                    $specializedDimensionSpacePoint = $generalization->getDimensionSpacePoint()->vary($dimensionIdentifier, (string) $specializedValue);
                    if (!$this->allowedDimensionSubspace->contains($specializedDimensionSpacePoint)) {
                        continue;
                    }
                    $specialization = $this->getWeightedDimensionSpacePointByDimensionSpacePoint($specializedDimensionSpacePoint);

                    if (!isset($normalizedVariationWeights[$specialization->getIdentityHash()])) {
                        $normalizedVariationWeights[$specialization->getIdentityHash()] = $specialization->getWeight()->normalize($this->determineWeightNormalizationBase());
                    }
                    $this->initializeVariationsForDimensionSpacePointPair($specialization, $generalization, $normalizedVariationWeights);
                    $normalizedVariationWeight = $normalizedVariationWeights[$specialization->getIdentityHash()] - $normalizedVariationWeights[$generalizationHash];
                    if (!isset($lowestVariationWeights[$specialization->getIdentityHash()]) || $normalizedVariationWeight < $lowestVariationWeights[$specialization->getIdentityHash()]) {
                        $this->primaryGeneralizations[$specialization->getIdentityHash()] = $generalization->getDimensionSpacePoint();
                    }
                }
            }
        }
    }

    /**
     * @param WeightedDimensionSpacePoint $specialization
     * @param WeightedDimensionSpacePoint $generalization
     * @param array $normalizedVariationWeights
     */
    protected function initializeVariationsForDimensionSpacePointPair(WeightedDimensionSpacePoint $specialization, WeightedDimensionSpacePoint $generalization, array $normalizedVariationWeights)
    {
        /** @var array|WeightedDimensionSpacePoint[] $generalizationsToProcess */
        $generalizationsToProcess = [$generalization->getIdentityHash() => $generalization];
        if (isset($this->indexedGeneralizations[$generalization->getIdentityHash()])) {
            foreach ($this->indexedGeneralizations[$generalization->getIdentityHash()] as $parentGeneralizationHash => $parentGeneralization) {
                $generalizationsToProcess[$parentGeneralizationHash] = $this->getWeightedDimensionSpacePointByHash($parentGeneralizationHash);
            }
        }

        foreach ($generalizationsToProcess as $generalizationHashToProcess => $generalizationToProcess) {
            $normalizedWeightDifference = abs($normalizedVariationWeights[$generalizationHashToProcess] - $normalizedVariationWeights[$specialization->getIdentityHash()]);
            $this->indexedGeneralizations[$specialization->getIdentityHash()][$generalizationToProcess->getIdentityHash()] = $generalizationToProcess->getDimensionSpacePoint();
            $this->weightedGeneralizations[$specialization->getIdentityHash()][$normalizedWeightDifference] = $generalizationToProcess->getDimensionSpacePoint();

            $this->indexedSpecializations[$generalizationToProcess->getIdentityHash()][$specialization->getIdentityHash()] = $specialization->getDimensionSpacePoint();
            $this->weightedSpecializations[$generalizationToProcess->getIdentityHash()][$normalizedWeightDifference][$specialization->getIdentityHash()] = $specialization->getDimensionSpacePoint();
        }
    }

    /**
     * Returns specializations of a subgraph indexed by hash
     *
     * @param Domain\ValueObject\DimensionSpacePoint $generalization
     * @return array|Domain\ValueObject\DimensionSpacePoint[]
     */
    public function getIndexedSpecializations(Domain\ValueObject\DimensionSpacePoint $generalization): array
    {
        if (is_null($this->indexedSpecializations)) {
            $this->initializeVariations();
        }

        return $this->indexedSpecializations[$generalization->getHash()] ?? [];
    }

    /**
     * Returns generalizations of a subgraph indexed by hash
     *
     * @param Domain\ValueObject\DimensionSpacePoint $specialization
     * @return array|Domain\ValueObject\DimensionSpacePoint[]
     */
    public function getIndexedGeneralizations(Domain\ValueObject\DimensionSpacePoint $specialization): array
    {
        if (is_null($this->indexedGeneralizations)) {
            $this->initializeVariations();
        }

        return $this->indexedGeneralizations[$specialization->getHash()] ?? [];
    }

    /**
     * Returns specializations of a subgraph indexed by relative weight
     *
     * @param Domain\ValueObject\DimensionSpacePoint $generalization
     * @return array|Domain\ValueObject\DimensionSpacePoint[]
     */
    public function getWeightedSpecializations(Domain\ValueObject\DimensionSpacePoint $generalization): array
    {
        if (is_null($this->weightedSpecializations)) {
            $this->initializeVariations();
        }

        return $this->weightedSpecializations[$generalization->getHash()] ?? [];
    }

    /**
     * Returns generalizations of a subgraph indexed by relative weight
     *
     * @param Domain\ValueObject\DimensionSpacePoint $specialization
     * @return array|Domain\ValueObject\DimensionSpacePoint[]
     */
    public function getWeightedGeneralizations(Domain\ValueObject\DimensionSpacePoint $specialization): array
    {
        if (is_null($this->weightedGeneralizations)) {
            $this->initializeVariations();
        }

        return $this->weightedGeneralizations[$specialization->getHash()] ?? [];
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
            $specializations = [];
            if ($includeOrigin) {
                $specializations[$origin->getHash()] = $origin;
            }

            foreach ($this->getIndexedSpecializations($origin) as $specialization) {
                if (!$excludedSet || !$excludedSet->contains($specialization)) {
                    $specializations[$specialization->getHash()] = $specialization;
                }
            }

            return new Domain\ValueObject\DimensionSpacePointSet($specializations);
        }
    }

    /**
     * @param Domain\ValueObject\DimensionSpacePoint $specialization
     * @return Domain\ValueObject\DimensionSpacePoint|null
     * @api
     */
    public function getPrimaryGeneralization(Domain\ValueObject\DimensionSpacePoint $specialization): ?Domain\ValueObject\DimensionSpacePoint
    {
        if (is_null($this->indexedGeneralizations)) {
            $this->initializeVariations();
        }

        return $this->primaryGeneralizations[$specialization->getHash()] ?? null;
    }
}
