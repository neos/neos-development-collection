<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\Flow\Annotations as Flow;

/**
 * The set containing dimension space points
 * * covered by a node aggregate
 * * and affected by a command
 * @Flow\Proxy(false)
 */
final class AffectedCoveredDimensionSpacePointSet
{
    public static function forStrategyIdentifier(
        NodeVariantSelectionStrategyIdentifier $identifier,
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePoint $referenceDimensionSpacePoint,
        InterDimensionalVariationGraph $variationGraph
    ): DimensionSpacePointSet {
        return match ($identifier) {
            NodeVariantSelectionStrategyIdentifier::STRATEGY_ALL_VARIANTS => self::allVariants($nodeAggregate),
            NodeVariantSelectionStrategyIdentifier::STRATEGY_ALL_SPECIALIZATIONS => self::allSpecializations(
                $nodeAggregate,
                $referenceDimensionSpacePoint,
                $variationGraph
            ),
            NodeVariantSelectionStrategyIdentifier::STRATEGY_VIRTUAL_SPECIALIZATIONS => self::virtualSpecializations(
                $nodeAggregate,
                $referenceDimensionSpacePoint,
                $variationGraph
            ),
            default => self::onlyGivenVariant($referenceDimensionSpacePoint),
        };
    }

    /**
     * When only the given dimension space point is to be selected, it also is the only covered one affected
     *
     * @param DimensionSpacePoint $referenceDimensionSpacePoint
     * @return DimensionSpacePointSet
     */
    public static function onlyGivenVariant(DimensionSpacePoint $referenceDimensionSpacePoint): DimensionSpacePointSet
    {
        return new DimensionSpacePointSet([$referenceDimensionSpacePoint]);
    }

    /**
     * When only the virtual specializations of the given dimension space point are to be selected,
     * then only those of its specializations that are not occupied by the node aggregate
     * or covered by a specialized node are affected
     *
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @param DimensionSpacePoint $referenceDimensionSpacePoint
     * @param InterDimensionalVariationGraph $variationGraph
     * @return DimensionSpacePointSet
     */
    public static function virtualSpecializations(
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePoint $referenceDimensionSpacePoint,
        InterDimensionalVariationGraph $variationGraph
    ): DimensionSpacePointSet {
        $specializationSet = $variationGraph->getSpecializationSet($referenceDimensionSpacePoint);
        $affectedDimensionSpacePoints = $specializationSet
            ->getIntersection($nodeAggregate->getCoveredDimensionSpacePoints());
        foreach ($specializationSet as $specializedDimensionSpacePoint) {
            if ($specializedDimensionSpacePoint->equals($referenceDimensionSpacePoint)) {
                continue;
            }
            if ($nodeAggregate->occupiesDimensionSpacePoint($specializedDimensionSpacePoint)) {
                $affectedDimensionSpacePoints = $affectedDimensionSpacePoints->getIntersection(
                    $variationGraph->getSpecializationSet($specializedDimensionSpacePoint)
                );
            }
        }

        return $affectedDimensionSpacePoints;
    }

    /**
     * When all specializations of the given dimension space point are to be selected,
     * then all of its specializations covered by the node aggregate are affected
     *
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @param DimensionSpacePoint $referenceDimensionSpacePoint
     * @param InterDimensionalVariationGraph $variationGraph
     * @return DimensionSpacePointSet
     */
    public static function allSpecializations(
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePoint $referenceDimensionSpacePoint,
        InterDimensionalVariationGraph $variationGraph
    ): DimensionSpacePointSet {
        $specializationSet = $variationGraph->getSpecializationSet($referenceDimensionSpacePoint);
        $affectedDimensionSpacePoints = $specializationSet
            ->getIntersection($nodeAggregate->getCoveredDimensionSpacePoints());

        return $affectedDimensionSpacePoints;
    }

    /**
     * When all variants of the given dimension space point are to be selected,
     * then all points covered by the node aggregate are affected
     *
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @return DimensionSpacePointSet
     */
    public static function allVariants(ReadableNodeAggregateInterface $nodeAggregate): DimensionSpacePointSet
    {
        return $nodeAggregate->getCoveredDimensionSpacePoints();
    }
}
