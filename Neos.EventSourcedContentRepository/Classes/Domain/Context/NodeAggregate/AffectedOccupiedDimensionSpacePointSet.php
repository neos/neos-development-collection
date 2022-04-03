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
        NodeVariantSelectionStrategy $identifier,
        ReadableNodeAggregateInterface $nodeAggregate,
        OriginDimensionSpacePoint $referenceDimensionSpacePoint,
        InterDimensionalVariationGraph $variationGraph
    ): OriginDimensionSpacePointSet {
        return match ($identifier) {
            NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS => self::allVariants($nodeAggregate),
            NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS => self::allSpecializations(
                $nodeAggregate,
                $referenceDimensionSpacePoint,
                $variationGraph
            ),
            NodeVariantSelectionStrategy::STRATEGY_VIRTUAL_SPECIALIZATIONS => self::virtualSpecializations(
                $nodeAggregate,
                $referenceDimensionSpacePoint
            ),
            default => self::onlyGivenVariant($nodeAggregate, $referenceDimensionSpacePoint),
        };
    }

    /**
     * When only the given dimension space point is to be selected,
     * it is affected only if also occupied
     */
    public static function onlyGivenVariant(
        ReadableNodeAggregateInterface $nodeAggregate,
        OriginDimensionSpacePoint $referenceDimensionSpacePoint
    ): OriginDimensionSpacePointSet {
        return $nodeAggregate->occupiesDimensionSpacePoint($referenceDimensionSpacePoint)
            ? new OriginDimensionSpacePointSet([$referenceDimensionSpacePoint])
            : new OriginDimensionSpacePointSet([]);
    }

    /**
     * When only the virtual specializations of the given dimension space point are to be selected,
     * only the given one is affected and only if also occupied
     */
    public static function virtualSpecializations(
        ReadableNodeAggregateInterface $nodeAggregate,
        OriginDimensionSpacePoint $referenceDimensionSpacePoint
    ): OriginDimensionSpacePointSet {
        return $nodeAggregate->occupiesDimensionSpacePoint($referenceDimensionSpacePoint)
            ? new OriginDimensionSpacePointSet([$referenceDimensionSpacePoint])
            : new OriginDimensionSpacePointSet([]);
    }

    /**
     * When all specializations of the given dimension space point are to be selected,
     * then all of its specializations occupied by the node aggregate are affected
     */
    public static function allSpecializations(
        ReadableNodeAggregateInterface $nodeAggregate,
        OriginDimensionSpacePoint $referenceDimensionSpacePoint,
        InterDimensionalVariationGraph $variationGraph
    ): OriginDimensionSpacePointSet {
        return OriginDimensionSpacePointSet::fromDimensionSpacePointSet(
            $variationGraph->getSpecializationSet($referenceDimensionSpacePoint->toDimensionSpacePoint())
        )->getIntersection($nodeAggregate->getOccupiedDimensionSpacePoints());
    }

    /**
     * When all variants of the given dimension space point are to be selected,
     * then all points occupied by the node aggregate are affected
     */
    public static function allVariants(ReadableNodeAggregateInterface $nodeAggregate): OriginDimensionSpacePointSet
    {
        return $nodeAggregate->getOccupiedDimensionSpacePoints();
    }
}
