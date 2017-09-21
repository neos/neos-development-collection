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
use Neos\ContentRepository\Domain;
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
     * @var array
     */
    protected $subgraphs = [];

    public function initializeObject()
    {
        $this->subgraphs = [];

        $dimensionValueCombinations = [[]];
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

    /**
     * @param array $dimensionValues
     * @return ContentSubgraph
     */
    public function createContentSubgraph(array $dimensionValues): ContentSubgraph
    {
        $subgraph = new ContentSubgraph($dimensionValues);
        $this->subgraphs[$subgraph->getIdentityHash()] = $subgraph;

        return $subgraph;
    }

    /**
     * @param ContentSubgraph $variant
     * @param ContentSubgraph $fallback
     * @return VariationEdge
     * @throws Dimension\Exception\InvalidFallbackException
     */
    public function connectSubgraphs(ContentSubgraph $variant, ContentSubgraph $fallback): VariationEdge
    {
        if ($variant === $fallback) {
            throw new Dimension\Exception\InvalidFallbackException();
        }

        return new VariationEdge($variant, $fallback, $this->calculateFallbackWeight($variant, $fallback));
    }

    /**
     * @param ContentSubgraph $variant
     * @param ContentSubgraph $fallback
     * @return array
     */
    public function calculateFallbackWeight(ContentSubgraph $variant, ContentSubgraph $fallback)
    {
        $weight = [];
        foreach ($this->intraDimensionalFallbackGraph->getPrioritizedContentDimensions() as $contentDimension) {
            $weight[$contentDimension->getName()] = $variant
                ->getDimensionValue($contentDimension->getName())
                ->calculateFallbackDepth($fallback->getDimensionValue($contentDimension->getName()));
        }

        return $weight;
    }

    /**
     * @param array $weight
     * @return int
     */
    public function normalizeWeight(array $weight): int
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
    public function determineWeightNormalizationBase(): int
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
    public function getPrimaryFallback(ContentSubgraph $contentSubgraph)
    {
        $fallbackEdges = $contentSubgraph->getFallbackEdges();
        if (empty($fallbackEdges)) {
            return null;
        }

        uasort($fallbackEdges, function (VariationEdge $edgeA, VariationEdge $edgeB) {
            return $this->normalizeWeight($edgeA->getWeight()) <=> $this->normalizeWeight($edgeB->getWeight());
        });

        return reset($fallbackEdges)->getFallback();
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
     * @param string $hash
     * @return ContentSubgraph|null
     */
    public function getSubgraphByDimensionSpacePointHash(string $hash): ?ContentSubgraph
    {
        return isset($this->subgraphs[$hash]) ? $this->subgraphs[$hash] : null;
    }
}
