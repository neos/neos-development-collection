<?php

namespace Neos\ContentRepository\Domain\Context\DimensionSpace\Repository;

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
use Neos\ContentRepository\Domain\Context\DimensionSpace;
use Neos\ContentRepository\Domain;
use Neos\ContentRepository\Exception\DimensionSpacePointNotFound;
use Neos\Flow\Annotations as Flow;

/**
 * The inter dimensional fallback graph domain model
 * Represents the fallback mechanism between content subgraphs
 *
 * @Flow\Scope("singleton")
 */
class InterDimensionalFallbackGraph
{
    /**
     * @Flow\Inject
     * @var Dimension\Repository\IntraDimensionalFallbackGraph
     */
    protected $intraDimensionalFallbackGraph;

    /**
     * @Flow\Inject
     * @var AllowedDimensionSubspace
     */
    protected $allowedDimensionSubspace;

    /**
     * @var array|ContentSubgraph[]
     */
    protected $subgraphs = [];

    public function initializeObject()
    {
        $this->subgraphs = [];

        $dimensionValueCombinations = [[]];
        if (empty($this->intraDimensionalFallbackGraph->getPrioritizedContentDimensions())) {
            $this->createContentSubgraph([]);
        } else {
            foreach ($this->intraDimensionalFallbackGraph->getPrioritizedContentDimensions() as $contentDimension) {
                $nextLevelValueCombinations = [];
                foreach ($dimensionValueCombinations as $previousCombination) {
                    foreach ($contentDimension->getValues() as $value) {
                        $combination = $previousCombination;
                        $combination[$contentDimension->getName()] = $value;

                        $nextLevelValueCombinations[] = $combination;
                    }
                }
                $dimensionValueCombinations = $nextLevelValueCombinations;
            }

            $edgeCount = 0;
            foreach ($dimensionValueCombinations as $dimensionValues) {
                /** @var Dimension\Model\ContentDimensionValue[] $dimensionValues */
                $coordinates = [];
                foreach ($dimensionValues as $dimensionName => $dimensionValue) {
                    $coordinates[$dimensionName] = $dimensionValue->getValue();
                }
                $dimensionSpacePoint = new Domain\ValueObject\DimensionSpacePoint($coordinates);
                if (!$this->allowedDimensionSubspace->contains($dimensionSpacePoint)) {
                    continue;
                }

                $newContentSubgraph = $this->createContentSubgraph($dimensionValues);
                foreach ($this->getSubgraphs() as $presentContentSubgraph) {
                    if ($presentContentSubgraph === $newContentSubgraph
                        || $this->normalizeWeight($newContentSubgraph->getWeight())
                        < $this->normalizeWeight($presentContentSubgraph->getWeight())
                    ) {
                        continue 2;
                    }
                    try {
                        $this->connectSubgraphs($newContentSubgraph, $presentContentSubgraph);
                        $edgeCount++;
                    } catch (Dimension\Exception\InvalidFallbackException $e) {
                        continue;
                    }
                }
            }
        }


        foreach ($this->allowedDimensionSubspace->getPoints() as $allowedDimensionSpacePoint) {
            if (!isset($this->subgraphs[$allowedDimensionSpacePoint->getHash()])) {
                throw new DimensionSpace\Exception\FallbackInitializationException(sprintf('Fallback initialization failed; %s (%s) was found in the allowed dimension subspace but was not initialized',
                    $allowedDimensionSpacePoint, $allowedDimensionSpacePoint->getHash()), 1506093011);
            }
        }
    }

    /**
     * @param array $dimensionValues
     * @return ContentSubgraph
     */
    protected function createContentSubgraph(array $dimensionValues): ContentSubgraph
    {
        $subgraph = new ContentSubgraph($dimensionValues);
        $this->subgraphs[$subgraph->getIdentityHash()] = $subgraph;

        return $subgraph;
    }

    /**
     * @param ContentSubgraph $specialization
     * @param ContentSubgraph $generalization
     * @return VariationEdge
     * @throws Dimension\Exception\InvalidFallbackException
     */
    protected function connectSubgraphs(ContentSubgraph $specialization, ContentSubgraph $generalization): VariationEdge
    {
        if ($specialization === $generalization) {
            throw new Dimension\Exception\InvalidFallbackException();
        }

        return new VariationEdge($specialization, $generalization, $this->calculateFallbackWeight($specialization, $generalization));
    }

    /**
     * @param ContentSubgraph $specialization
     * @param ContentSubgraph $generalization
     * @return array
     */
    protected function calculateFallbackWeight(ContentSubgraph $specialization, ContentSubgraph $generalization)
    {
        $weight = [];
        foreach ($this->intraDimensionalFallbackGraph->getPrioritizedContentDimensions() as $contentDimension) {
            $weight[$contentDimension->getName()] = $specialization
                ->getDimensionValue($contentDimension->getName())
                ->calculateFallbackDepth($generalization->getDimensionValue($contentDimension->getName()));
        }

        return $weight;
    }

    /**
     * @param array $weight
     * @return int
     */
    protected function normalizeWeight(array $weight): int
    {
        $base = $this->determineWeightNormalizationBase();
        $normalizedWeight = 0;
        $exponent = 0;
        foreach (array_reverse($weight) as $dimensionName => $dimensionFallbackWeight) {
            $normalizedWeight += pow($base, $exponent) * $dimensionFallbackWeight;
            $exponent++;
        }

        return $normalizedWeight;
    }

    /**
     * @return int
     */
    protected function determineWeightNormalizationBase(): int
    {
        $base = 0;
        foreach ($this->intraDimensionalFallbackGraph->getPrioritizedContentDimensions() as $contentDimension) {
            $base = max($base, $contentDimension->getDepth() + 1);
        }

        return $base;
    }

    /**
     * @param ContentSubgraph $contentSubgraph
     * @return ContentSubgraph|null
     * @api
     */
    public function getPrimaryFallback(ContentSubgraph $contentSubgraph): ?ContentSubgraph
    {
        $generalizations = $contentSubgraph->getGeneralizationEdges();
        if (empty($generalizations)) {
            return null;
        }

        uasort($generalizations, function (VariationEdge $edgeA, VariationEdge $edgeB) {
            return $this->normalizeWeight($edgeA->getWeight()) <=> $this->normalizeWeight($edgeB->getWeight());
        });

        return reset($generalizations)->getGeneralization();
    }

    /**
     * @return array|ContentSubgraph[]
     * @api
     */
    public function getSubgraphs(): array
    {
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
     * @param Domain\ValueObject\DimensionSpacePoint $origin
     * @param bool $includeOrigin
     * @param Domain\ValueObject\DimensionSpacePointSet|null $excludedSet
     * @return Domain\ValueObject\DimensionSpacePointSet
     * @throws DimensionSpacePointNotFound
     * @throws DimensionSpace\Exception\FallbackInitializationException
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
            if (!$subgraph) {
                throw new DimensionSpace\Exception\FallbackInitializationException(
                    sprintf('Fallback initialization failed; %s was found in the allowed dimension subspace but was not initialized', $origin),
                    1506093011
                );
            }
            $specializations = [];
            if ($includeOrigin) {
                $specializations[$origin->getHash()] = $origin;
            }
            if ($excludedSet) {
                $excludedSet = $this->completeSet($excludedSet);
            }
            foreach ($subgraph->getSpecializations() as $specialization) {
                if (!$excludedSet || !$excludedSet->contains($specialization->getDimensionSpacePoint())) {
                    $specializations[$specialization->getDimensionSpacePoint()->getHash()] = $specialization->getDimensionSpacePoint();
                }
            }

            return new Domain\ValueObject\DimensionSpacePointSet($specializations);
        }
    }

    /**
     * @param Domain\ValueObject\DimensionSpacePointSet $set
     * @return Domain\ValueObject\DimensionSpacePointSet
     */
    protected function completeSet(Domain\ValueObject\DimensionSpacePointSet $set): Domain\ValueObject\DimensionSpacePointSet
    {
        $completeSet = [];
        foreach ($set->getPoints() as $point) {
            foreach ($this->getSpecializationSet($point)->getPoints() as $specialization) {
                $completeSet[$specialization->getHash()] = $specialization;
            }
        }

        return new Domain\ValueObject\DimensionSpacePointSet($completeSet);
    }

    /**
     * @param string $hash
     * @return ContentSubgraph|null
     */
    public function getSubgraphByDimensionSpacePointHash(string $hash): ?ContentSubgraph
    {
        return isset($this->subgraphs[$hash]) ? $this->subgraphs[$hash] : null;
    }
}
