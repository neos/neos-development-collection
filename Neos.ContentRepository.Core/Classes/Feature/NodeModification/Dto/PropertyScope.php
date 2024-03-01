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

namespace Neos\ContentRepository\Core\Feature\NodeModification\Dto;

use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * The property scope to be used in NodeType property declarations.
 * Will affect node operations on properties in that they decide which of the node's variants will be modified as well.
 *
 * @api used as part of commands
 */
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
        NodeAggregate $nodeAggregate,
        InterDimensionalVariationGraph $variationGraph
    ): OriginDimensionSpacePointSet {
        return match ($this) {
            PropertyScope::SCOPE_NODE => new OriginDimensionSpacePointSet([$origin]),
            PropertyScope::SCOPE_SPECIALIZATIONS => OriginDimensionSpacePointSet::fromDimensionSpacePointSet(
                $variationGraph->getSpecializationSet(
                    $origin->toDimensionSpacePoint()
                )
            )->getIntersection($nodeAggregate->occupiedDimensionSpacePoints),
            PropertyScope::SCOPE_NODE_AGGREGATE => $nodeAggregate->occupiedDimensionSpacePoints
        };
    }

    public static function tryFromDeclaration(NodeType $nodeType, PropertyName $propertyName): self
    {
        $declaration = $nodeType->getProperties()[$propertyName->value]['scope'] ?? null;
        if (is_string($declaration)) {
            return PropertyScope::from($declaration);
        }
        return PropertyScope::SCOPE_NODE;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
