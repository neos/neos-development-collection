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

namespace Neos\ContentRepository\Projection\ContentGraph;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Projection\ProjectionStateInterface;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;

/**
 * This is the MAIN ENTRY POINT for the Content Repository. This class exists only
 * **once per Content Repository**.
 *
 * The most important API method is {@see ContentGraphInterface::getSubgraphByIdentifier()},
 * where you can access the most important read model, the {@see ContentSubgraphInterface}.
 *
 * @api
 */
interface ContentGraphInterface extends ProjectionStateInterface
{
    public function getSubgraphByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface;

    public function findNodeByIdentifiers(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): ?NodeInterface;

    public function findRootNodeAggregateByType(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName
    ): NodeAggregate;

    /**
     * @return iterable<NodeAggregate>
     */
    public function findNodeAggregatesByType(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName
    ): iterable;

    /**
     * @throws NodeAggregatesTypeIsAmbiguous
     */
    public function findNodeAggregateByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): ?NodeAggregate;

    /**
     *
     * @internal only for consumption inside the Command Handler
     */
    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate;

    /**
     * @return iterable<NodeAggregate>
     * @internal only for consumption inside the Command Handler
     */
    public function findParentNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier
    ): iterable;

    /**
     * @return iterable<NodeAggregate>
     * @internal only for consumption inside the Command Handler
     */
    public function findChildNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): iterable;

    /**
     * A node aggregate may have multiple child node aggregates with the same name
     * as long as they do not share dimension space coverage
     *
     * @return iterable<NodeAggregate>
     * @internal only for consumption inside the Command Handler
     */
    public function findChildNodeAggregatesByName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $name
    ): iterable;

    /**
     * @return iterable<NodeAggregate>
     * @internal only for consumption inside the Command Handler
     */
    public function findTetheredChildNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): iterable;

    /**
     * @internal only for consumption inside the Command Handler
     */
    public function getDimensionSpacePointsOccupiedByChildNodeName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeName $nodeName,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet;

    public function countNodes(): int;

    /**
     * Returns all node types in use, from the graph projection
     *
     * @return iterable<NodeTypeName>
     */
    public function findUsedNodeTypeNames(): iterable;
}
