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

namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamState;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\EventStore\Model\EventStream\MaybeVersion;

/**
 * This is a read API provided for constraint checks within the write side.
 * It must be bound to a contentStreamId and workspaceName on creation.
 *
 * @api only for consumption in command handlers and content graph services
 */
interface ContentGraphAdapterInterface
{
    /*
     * EXPOSING INTERNAL STATE
     */

    public function getWorkspaceName(): WorkspaceName;

    public function getContentStreamId(): ContentStreamId;


    /*
     * NODE AGGREGATES
     */

    public function rootNodeAggregateWithTypeExists(
        NodeTypeName $nodeTypeName
    ): bool;

    public function findRootNodeAggregateByType(
        NodeTypeName $nodeTypeName
    ): ?NodeAggregate;

    /**
     * @return iterable<NodeAggregate>
     */
    public function findParentNodeAggregates(
        NodeAggregateId $childNodeAggregateId
    ): iterable;

    /**
     * @throws NodeAggregatesTypeIsAmbiguous
     */
    public function findNodeAggregateById(
        NodeAggregateId $nodeAggregateId
    ): ?NodeAggregate;

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate;

    /**
     * @return iterable<NodeAggregate>
     */
    public function findChildNodeAggregates(
        NodeAggregateId $parentNodeAggregateId
    ): iterable;

    /**
     * @return iterable<NodeAggregate>
     */
    public function findTetheredChildNodeAggregates(
        NodeAggregateId $parentNodeAggregateId
    ): iterable;

    /**
     */
    public function getDimensionSpacePointsOccupiedByChildNodeName(
        NodeName $nodeName,
        NodeAggregateId $parentNodeAggregateId,
        OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet;

    /**
     * A node aggregate may have multiple child node aggregates with the same name
     * as long as they do not share dimension space coverage
     *
     * @return iterable<NodeAggregate>
     */
    public function findChildNodeAggregatesByName(
        NodeAggregateId $parentNodeAggregateId,
        NodeName $name
    ): iterable;

    /*
     * NODES, basically anything you would ask a subgraph
     */

    /**
     * Does the subgraph with the provided identity contain any nodes
     */
    public function subgraphContainsNodes(
        DimensionSpacePoint $dimensionSpacePoint
    ): bool;

    /**
     * Finds a specified node within a "subgraph"
     */
    public function findNodeInSubgraph(
        DimensionSpacePoint $coveredDimensionSpacePoint,
        NodeAggregateId $nodeAggregateId
    ): ?Node;

    public function findParentNodeInSubgraph(
        DimensionSpacePoint $coveredDimensionSpacePoint,
        NodeAggregateId $childNodeAggregateId
    ): ?Node;

    public function findChildNodesInSubgraph(
        DimensionSpacePoint $coveredDimensionSpacePoint,
        NodeAggregateId $parentNodeAggregateId
    ): Nodes;

    public function findChildNodeByNameInSubgraph(
        DimensionSpacePoint $coveredDimensionSpacePoint,
        NodeAggregateId $parentNodeAggregateId,
        NodeName $nodeName
    ): ?Node;

    public function findPreceedingSiblingNodesInSubgraph(
        DimensionSpacePoint $coveredDimensionSpacePoint,
        NodeAggregateId $startingSiblingNodeAggregateId
    ): Nodes;

    public function findSucceedingSiblingNodesInSubgraph(
        DimensionSpacePoint $coveredDimensionSpacePoint,
        NodeAggregateId $startingSiblingNodeAggregateId
    ): Nodes;

    /*
     * CONTENT STREAMS
     */

    public function hasContentStream(): bool;

    public function findStateForContentStream(): ?ContentStreamState;

    public function findVersionForContentStream(): MaybeVersion;

    /*
     * WORKSPACES
     */

    public function getWorkspace(): Workspace;
}
