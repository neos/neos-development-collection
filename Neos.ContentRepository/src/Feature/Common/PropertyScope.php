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

namespace Neos\ContentRepository\Feature\Common;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;

/**
 * The property scope to be used in NodeType property declarations.
 * Will affect node operations on properties in that they decide which of the node's variants will be modified as well.
 */
ContextGetRootNodeRector
enum PropertyScope: string implements \JsonSerializable
{
    /**
     * The "node" scope, meaning only the node in the selected origin will be modified
     */
    case SCOPE_NODE = 'node';

    /**
     * The "specializations" scope, meaning only the node and its specializations will be modified
     */
    case SCOPE_SPECIALIZATIONS = 'specializations';

    /**
     * The "nodeAggregate" scope, meaning that all variants, e.g. all nodes in the aggregate will be modified
     */
    case SCOPE_NODE_AGGREGATE = 'nodeAggregate';

    public function resolveAffectedOrigins(
        OriginDimensionSpacePoint $origin,
        ReadableNodeAggregateInterface $nodeAggregate,
        InterDimensionalVariationGraph $variationGraph
    ): OriginDimensionSpacePointSet {
        return match ($this) {
            PropertyScope::SCOPE_NODE => new OriginDimensionSpacePointSet([$origin]),
            PropertyScope::SCOPE_SPECIALIZATIONS => OriginDimensionSpacePointSet::fromDimensionSpacePointSet(
                $variationGraph->getSpecializationSet(
                    $origin->toDimensionSpacePoint()
                )
            )->getIntersection($nodeAggregate->getOccupiedDimensionSpacePoints()),
            PropertyScope::SCOPE_NODE_AGGREGATE => $nodeAggregate->getOccupiedDimensionSpacePoints()
        };
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
