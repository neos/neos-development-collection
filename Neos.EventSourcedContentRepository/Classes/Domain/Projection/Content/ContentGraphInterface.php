<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\DBALException;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;

/**
 * The interface to be implemented by content graphs
 */
interface ContentGraphInterface
{
    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param Domain\Context\Parameters\VisibilityConstraints $visibilityConstraints
     * @return ContentSubgraphInterface|null
     */
    public function getSubgraphByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        Domain\Context\Parameters\VisibilityConstraints $visibilityConstraints
    ): ?ContentSubgraphInterface;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param OriginDimensionSpacePoint $originDimensionSpacePoint
     * @return NodeInterface|null
     * @throws DBALException
     */
    public function findNodeByIdentifiers(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): ?NodeInterface;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeTypeName $nodeTypeName
     * @return NodeAggregate
     */
    public function findRootNodeAggregateByType(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName
    ): NodeAggregate;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeTypeName $nodeTypeName
     * @return NodeAggregate[]|iterable<NodeAggregate>
     */
    public function findNodeAggregatesByType(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName
    ): iterable;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return NodeAggregate|null
     * @throws NodeAggregatesTypeIsAmbiguous
     */
    public function findNodeAggregateByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): ?NodeAggregate;

    /**
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $childNodeAggregateIdentifier
     * @param OriginDimensionSpacePoint $childOriginDimensionSpacePoint
     * @return NodeAggregate|null
     * @internal only for consumption inside the Command Handler
     */
    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $childNodeAggregateIdentifier
     * @return iterable<NodeAggregate>
     * @internal only for consumption inside the Command Handler
     */
    public function findParentNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier
    ): iterable;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
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
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeName $name
     * @return iterable<NodeAggregate>
     * @internal only for consumption inside the Command Handler
     */
    public function findChildNodeAggregatesByName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $name
    ): iterable;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @return iterable<NodeAggregate>
     * @internal only for consumption inside the Command Handler
     */
    public function findTetheredChildNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): iterable;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeName $nodeName
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param OriginDimensionSpacePoint $parentNodeOriginOriginDimensionSpacePoint
     * @param DimensionSpacePointSet $dimensionSpacePointsToCheck
     * @return DimensionSpacePointSet
     * @internal only for consumption inside the Command Handler
     */
    public function getDimensionSpacePointsOccupiedByChildNodeName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeName $nodeName,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        OriginDimensionSpacePoint $parentNodeOriginOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet;

    public function countNodes(): int;

    /**
     * Returns all node types in use, from the graph projection
     *
     * @return iterable<NodeTypeName>
     */
    public function findUsedNodeTypeNames(): iterable;

    /**
     * Enable all caches. All READ requests should enable the cache.
     * By default, caches are enabled!
     */
    public function enableCache(): void;

    /**
     * Disable all caches. All WRITE requests should disable the cache.
     */
    public function disableCache(): void;
}
