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

namespace Neos\ContentRepository\Core\SharedModel\Node;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;

/**
 * The node variant selection strategy for node aggregates as selected when creating commands.
 * Used for calculating the affected dimension space points
 * to e.g. build restriction relations to other node aggregates or to remove nodes.
 *
 * To further explain this, consider the following variation graph:
 * ┌─────┐     ┌─────┐     ┌─────┐     ┌─────┐
 * │ gsw │────▶│ de  │────▶│ en  │◀────│ fr  │
 * └─────┘     └─────┘     └─────┘     └─────┘
 * From "de"'s point of view, "gsw" is a specialization, "en" a generalization and "fr" a peer variant
 * {@see VariantType}
 * For our examples we consider a node aggregate covering the complete dimension space.
 *
 * @api used as part of commands
 */
enum NodeVariantSelectionStrategy: string implements \JsonSerializable
{
    /**
     * The "all specializations" strategy, meaning all specializations covered by this node aggregate are affected
     *
     * In our example, the result of using "allSpecializations" on "de" will be ["de","gsw"]
     */
    case STRATEGY_ALL_SPECIALIZATIONS = 'allSpecializations';

    /**
     * The "all variants" strategy, meaning all dimension space points covered by this node aggregate are affected
     *
     * In our example, the result of using "allVariants" on "de" will be ["de","gsw","en","fr"]
     */
    case STRATEGY_ALL_VARIANTS = 'allVariants';

    public function resolveAffectedDimensionSpacePoints(
        DimensionSpacePoint $referenceDimensionSpacePoint,
        NodeAggregate $nodeAggregate,
        InterDimensionalVariationGraph $variationGraph
    ): DimensionSpacePointSet {
        return match ($this) {
            self::STRATEGY_ALL_VARIANTS => $nodeAggregate->coveredDimensionSpacePoints,
            self::STRATEGY_ALL_SPECIALIZATIONS => $variationGraph->getSpecializationSet($referenceDimensionSpacePoint)
                ->getIntersection($nodeAggregate->coveredDimensionSpacePoints)
        };
    }

    public function resolveAffectedOriginDimensionSpacePoints(
        OriginDimensionSpacePoint $referenceDimensionSpacePoint,
        NodeAggregate $nodeAggregate,
        InterDimensionalVariationGraph $variationGraph
    ): OriginDimensionSpacePointSet {
        return match ($this) {
            self::STRATEGY_ALL_VARIANTS => $nodeAggregate->occupiedDimensionSpacePoints,
            self::STRATEGY_ALL_SPECIALIZATIONS => OriginDimensionSpacePointSet::fromDimensionSpacePointSet(
                $variationGraph->getSpecializationSet($referenceDimensionSpacePoint->toDimensionSpacePoint())
            )->getIntersection($nodeAggregate->occupiedDimensionSpacePoints)
        };
    }

    public function equals(self $other): bool
    {
        return $this === $other;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
