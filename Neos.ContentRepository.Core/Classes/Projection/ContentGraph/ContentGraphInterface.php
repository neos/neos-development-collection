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

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * This is the MAIN ENTRY POINT for the Content Repository. This class exists only
 * **once per Content Repository**.
 *
 * The most important API method is {@see ContentGraphInterface::getSubgraph()},
 * where you can access the most important read model, the {@see ContentSubgraphInterface}.
 *
 * @api only the methods marked as API
 */
interface ContentGraphInterface extends ProjectionStateInterface
{
    /**
     * @api
     */
    public function getContentRepositoryId(): ContentRepositoryId;

    /**
     * The workspace this content graph is operating on
     * @api
     */
    public function getWorkspaceName(): WorkspaceName;

    /**
     * @api main API method of ContentGraph
     */
    public function getSubgraph(
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface;

    /**
     * @api
     */
    public function findRootNodeAggregateByType(
        NodeTypeName $nodeTypeName
    ): ?NodeAggregate;

    /**
     * @api
     */
    public function findRootNodeAggregates(
        Filter\FindRootNodeAggregatesFilter $filter,
    ): NodeAggregates;

    /**
     * @return iterable<NodeAggregate>
     * @api
     */
    public function findNodeAggregatesByType(
        NodeTypeName $nodeTypeName
    ): iterable;

    /**
     * @throws NodeAggregatesTypeIsAmbiguous
     * @api
     */
    public function findNodeAggregateById(
        NodeAggregateId $nodeAggregateId
    ): ?NodeAggregate;

    /**
     * Returns all node types in use, from the graph projection
     *
     * @return iterable<NodeTypeName>
     * @api
     */
    public function findUsedNodeTypeNames(): iterable;

    /**
     * @internal only for consumption inside the Command Handler
     */
    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate;

    /**
     * @return iterable<NodeAggregate>
     * @internal only for consumption inside the Command Handler
     */
    public function findParentNodeAggregates(
        NodeAggregateId $childNodeAggregateId
    ): iterable;

    /**
     * @return iterable<NodeAggregate>
     * @internal only for consumption inside the Command Handler
     */
    public function findChildNodeAggregates(
        NodeAggregateId $parentNodeAggregateId
    ): iterable;

    /**
     * A node aggregate can have no or exactly one child node aggregate with a given name as enforced by constraint checks
     *
     * @internal only for consumption inside the Command Handler
     */
    public function findChildNodeAggregateByName(
        NodeAggregateId $parentNodeAggregateId,
        NodeName $name
    ): ?NodeAggregate;

    /**
     * @return iterable<NodeAggregate>
     * @internal only for consumption inside the Command Handler
     */
    public function findTetheredChildNodeAggregates(
        NodeAggregateId $parentNodeAggregateId
    ): iterable;

    /**
     * @internal only for consumption inside the Command Handler
     */
    public function getDimensionSpacePointsOccupiedByChildNodeName(
        NodeName $nodeName,
        NodeAggregateId $parentNodeAggregateId,
        OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet;

    /**
     * Provides the total number of projected nodes regardless of workspace or content stream.
     *
     * @internal only for consumption in testcases
     */
    public function countNodes(): int;

    /** @internal The content stream id where the workspace name points to for this instance */
    public function getContentStreamId(): ContentStreamId;
}
