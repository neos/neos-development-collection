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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
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
     */
    public function findNodeByIdentifiers(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): ?NodeInterface;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeTypeName $nodeTypeName
     * @return NodeAggregate|null
     */
    public function findRootNodeAggregateByType(ContentStreamIdentifier $contentStreamIdentifier, NodeTypeName $nodeTypeName): ?NodeAggregate;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeTypeName $nodeTypeName
     * @return NodeAggregate[]|\Iterator
     */
    public function findNodeAggregatesByType(ContentStreamIdentifier $contentStreamIdentifier, NodeTypeName $nodeTypeName): \Iterator;

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

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return array|NodeAggregate[]
     */
    public function findParentNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): array;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @return array|NodeAggregate[]
     */
    public function findChildNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): array;

    /**
     * A node aggregate may have multiple child node aggregates with the same name
     * as long as they do not share dimension space coverage
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeName $name
     * @return array|NodeAggregate[]
     */
    public function findChildNodeAggregatesByName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $name
    ): array;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @return array|NodeAggregate[]
     */
    public function findTetheredChildNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): array;

    public function getDimensionSpacePointsOccupiedByChildNodeName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeName $nodeName,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        OriginDimensionSpacePoint $parentNodeOriginOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet;

    public function countNodes(): int;

    /**
     * Returns all content stream identifiers
     *
     * @return ContentStreamIdentifier[]
     */
    public function findContentStreamIdentifiers(): array;

    /**
     * Returns all node types in use, from the graph projection
     *
     * @return NodeTypeName[]
     */
    public function findProjectedNodeTypes(): iterable;

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
