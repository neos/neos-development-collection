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
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Core\SharedModel\Exception\RootNodeAggregateDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

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
     * @api main API method of ContentGraph
     */
    public function getSubgraph(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface;

    /**
     * @api
     * Throws exception if no root aggregate found, because a Content Repository needs at least
     * one root node to function.
     *
     * Also throws exceptions if multiple root node aggregates of the given $nodeTypeName were found,
     * as this would lead to nondeterministic results in your code.
     *
     * @throws RootNodeAggregateDoesNotExist
     */
    public function findRootNodeAggregateByType(
        ContentStreamId $contentStreamId,
        NodeTypeName $nodeTypeName
    ): NodeAggregate;

    /**
     * @api
     */
    public function findRootNodeAggregates(
        ContentStreamId $contentStreamId,
        Filter\FindRootNodeAggregatesFilter $filter,
    ): NodeAggregates;

    /**
     * @return iterable<NodeAggregate>
     * @api
     */
    public function findNodeAggregatesByType(
        ContentStreamId $contentStreamId,
        NodeTypeName $nodeTypeName
    ): iterable;

    /**
     * @throws NodeAggregatesTypeIsAmbiguous
     * @api
     */
    public function findNodeAggregateById(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId
    ): ?NodeAggregate;

    /**
     * @param ContentStreamId $contentStreamId
     * @param NodeAggregateId $childNodeAggregateId
     * @return iterable<NodeAggregate>
     */
    public function findParentNodeAggregates(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId
    ): iterable;

    /**
     * @param ContentStreamId $contentStreamId
     * @param NodeAggregateId $parentNodeAggregateId
     * @param NodeName $name
     * @return iterable<NodeAggregate>
     */
    public function findChildNodeAggregatesByName(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId,
        NodeName $name
    ): iterable;

    /**
     * Returns all node types in use, from the graph projection
     *
     * @return iterable<NodeTypeName>
     * @api
     */
    public function findUsedNodeTypeNames(): iterable;

    /**
     * @internal only for consumption in testcases
     */
    public function countNodes(): int;
}
