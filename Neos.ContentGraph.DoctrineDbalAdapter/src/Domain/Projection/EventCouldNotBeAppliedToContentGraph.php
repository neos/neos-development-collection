<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The exception to be thrown if an event could not be applied to the content graph
 * @internal
 */
final class EventCouldNotBeAppliedToContentGraph extends \DomainException
{
    public static function becauseTheSourceNodeIsMissing(string $variantType, ContentStreamId $contentStreamId, NodeAggregateId $nodeAggregateId, OriginDimensionSpacePoint|DimensionSpacePoint $dimensionSpacePoint): self
    {
        return new self(
            sprintf('Failed to create node %s variant for node "%s" in sub graph %s@%s because the source node is missing', $variantType, $nodeAggregateId->value, $dimensionSpacePoint->toJson(), $contentStreamId->value),
            1645315210
        );
    }

    public static function becauseTheSourceParentNodeIsMissing(string $variantType, ContentStreamId $contentStreamId, NodeAggregateId $nodeAggregateId, OriginDimensionSpacePoint|DimensionSpacePoint $dimensionSpacePoint): self
    {
        return new self(
            sprintf('Failed to create node %s variant for node "%s" in sub graph %s@%s because the source parent node is missing', $variantType, $nodeAggregateId->value, $dimensionSpacePoint->toJson(), $contentStreamId->value),
            1645315229
        );
    }

    public static function becauseTheTargetParentNodeIsMissing(string $variantType, ContentStreamId $contentStreamId, NodeAggregateId $nodeAggregateId, OriginDimensionSpacePoint|DimensionSpacePoint $dimensionSpacePoint, NodeAggregateId $parentNodeAggregateId): self
    {
        return new self(
            sprintf('Failed to create node %s variant for node "%s" in sub graph %s@%s because the target parent node "%s" is missing', $variantType, $nodeAggregateId->value, $dimensionSpacePoint->toJson(), $contentStreamId->value, $parentNodeAggregateId->value),
            1645315274
        );
    }

    public static function becauseTheIngoingSourceHierarchyRelationIsMissing(string $variantType, ContentStreamId $contentStreamId, NodeAggregateId $nodeAggregateId, OriginDimensionSpacePoint|DimensionSpacePoint $dimensionSpacePoint): self
    {
        return new self(
            sprintf('Failed to create node %s variant for node "%s" in sub graph %s@%s because the ingoing hierarchy relation is missing', $variantType, $nodeAggregateId->value, $dimensionSpacePoint->toJson(), $contentStreamId->value),
            1645317567
        );
    }
}
