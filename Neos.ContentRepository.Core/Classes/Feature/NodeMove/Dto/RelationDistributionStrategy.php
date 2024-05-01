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

namespace Neos\ContentRepository\Core\Feature\NodeMove\Dto;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;

/**
 * The relation distribution strategy for node aggregates as defined in the NodeType declaration
 * Used for building relations to other node aggregates
 *
 * - `scatter` means that different nodes within the aggregate may be related to different other aggregates
 *      (e.g. parent).
 * - `gatherAll` means that all nodes within the aggregate must be related to the same other aggregate (e.g. parent)
 * - `gatherSpecializations` means that when a node is related to another node aggregate (e.g. parent),
 *      all specializations of that node will be related to that same aggregate while generalizations
 *      may be related to others
 * - `gatherVirtualSpecializations` means that when a node is related to another node aggregate (e.g. parent),
 *      all virtual specializations of that node will be related to that same aggregate.
 *      This affects only specializations pointing to the same node using the fallback mechanism,
 *      i.e. the node has not been varied to there.
 *
 * @api DTO of {@see MoveNodeAggregate} command
 */
enum RelationDistributionStrategy: string implements \JsonSerializable
{
    case STRATEGY_SCATTER = 'scatter';
    case STRATEGY_GATHER_ALL = 'gatherAll';
    case STRATEGY_GATHER_SPECIALIZATIONS = 'gatherSpecializations';
    case STRATEGY_GATHER_VIRTUAL_SPECIALIZATIONS = 'gatherVirtualSpecializations';

    public static function fromString(?string $serialization): self
    {
        return !is_null($serialization)
            ? self::from($serialization)
            : self::STRATEGY_GATHER_ALL;
    }

    public function resolveAffectedDimensionSpacePointSet(
        NodeAggregate $nodeAggregate,
        DimensionSpacePoint $referenceDimensionSpacePoint,
        InterDimensionalVariationGraph $variationGraph,
    ): DimensionSpacePointSet {
        return match ($this) {
            self::STRATEGY_GATHER_ALL => $nodeAggregate->coveredDimensionSpacePoints,
            self::STRATEGY_SCATTER => new DimensionSpacePointSet([$referenceDimensionSpacePoint]),
            self::STRATEGY_GATHER_SPECIALIZATIONS => $nodeAggregate->coveredDimensionSpacePoints->getIntersection(
                $variationGraph->getSpecializationSet($referenceDimensionSpacePoint)
            ),
            self::STRATEGY_GATHER_VIRTUAL_SPECIALIZATIONS => $this->resolveVirtualSpecializations(
                $nodeAggregate,
                $referenceDimensionSpacePoint,
                $variationGraph
            )
        };
    }

    private function resolveVirtualSpecializations(
        NodeAggregate $nodeAggregate,
        DimensionSpacePoint $referenceDimensionSpacePoint,
        InterDimensionalVariationGraph $variationGraph
    ): DimensionSpacePointSet {
        $affectedDimensionSpacePoints = [];
        foreach ($variationGraph->getSpecializationSet($referenceDimensionSpacePoint) as $dimensionSpacePoint) {
            if (
                !$dimensionSpacePoint->equals($referenceDimensionSpacePoint)
                && $nodeAggregate->occupiesDimensionSpacePoint(
                    OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint)
                )
            ) {
                break;
            }
            $affectedDimensionSpacePoints[] = $dimensionSpacePoint;
        }

        return DimensionSpacePointSet::fromArray($affectedDimensionSpacePoints);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
