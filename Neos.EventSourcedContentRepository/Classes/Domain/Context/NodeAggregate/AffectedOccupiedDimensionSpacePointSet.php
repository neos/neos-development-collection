<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\Flow\Annotations as Flow;

/**
 * The set containing dimension space points
 * * occupied by a node aggregate
 * * and affected by a command
 */
#[Flow\Proxy(false)]
final class AffectedOccupiedDimensionSpacePointSet
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
                $referenceDimensionSpacePoint
            ),
            default => self::onlyGivenVariant($nodeAggregate, $referenceDimensionSpacePoint),
        };
    }

    /**
     * When only the the given dimension space point is to be selected,
     * it is affected only if also occupied
     *
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @param DimensionSpacePoint $referenceDimensionSpacePoint
     * @return DimensionSpacePointSet
     */
    public static function onlyGivenVariant(
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePoint $referenceDimensionSpacePoint
    ): DimensionSpacePointSet {
        return $nodeAggregate->occupiesDimensionSpacePoint($referenceDimensionSpacePoint)
            ? new DimensionSpacePointSet([$referenceDimensionSpacePoint])
            : new DimensionSpacePointSet([]);
    }

    /**
     * When only the virtual specializations of the given dimension space point are to be selected,
     * only the given one is affected and only if also occupied
     *
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @param DimensionSpacePoint $referenceDimensionSpacePoint
     * @return DimensionSpacePointSet
     */
    public static function virtualSpecializations(
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePoint $referenceDimensionSpacePoint
    ): DimensionSpacePointSet {
        return $nodeAggregate->occupiesDimensionSpacePoint($referenceDimensionSpacePoint)
            ? new DimensionSpacePointSet([$referenceDimensionSpacePoint])
            : new DimensionSpacePointSet([]);
    }

    /**
     * When all specializations of the given dimension space point are to be selected,
     * then all of its specializations occupied by the node aggregate are affected
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
        return $variationGraph->getSpecializationSet($referenceDimensionSpacePoint)
            ->getIntersection($nodeAggregate->getOccupiedDimensionSpacePoints()->toDimensionSpacePointSet());
    }

    /**
     * When all variants of the given dimension space point are to be selected,
     * then all points occupied by the node aggregate are affected
     *
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @return DimensionSpacePointSet
     */
    public static function allVariants(ReadableNodeAggregateInterface $nodeAggregate): DimensionSpacePointSet
    {
        return $nodeAggregate->getOccupiedDimensionSpacePoints()->toDimensionSpacePointSet();
    }
}
