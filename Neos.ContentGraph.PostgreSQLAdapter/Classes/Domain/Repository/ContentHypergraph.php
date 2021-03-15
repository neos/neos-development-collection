<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeAggregate;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;

/**
 * The PostgreSQL adapter content graph
 *
 * To be used as a read-only source of nodes
 *
 * @Flow\Scope("singleton")
 * @api
 */
final class ContentHypergraph implements ContentGraphInterface
{
    public function getSubgraphByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        Domain\Context\Parameters\VisibilityConstraints $visibilityConstraints
    ): ?ContentSubgraphInterface {
        // TODO: Implement getSubgraphByIdentifier() method.
    }

    public function findNodeByIdentifiers(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): ?NodeInterface {
        // TODO: Implement findNodeByIdentifiers() method.
    }

    public function findRootNodeAggregateByType(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName
    ): ?NodeAggregate {
        // TODO: Implement findRootNodeAggregateByType() method.
    }

    public function findNodeAggregatesByType(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeTypeName $nodeTypeName
    ): \Iterator {
        // TODO: Implement findNodeAggregatesByType() method.
    }

    public function findNodeAggregateByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): ?NodeAggregate {
        // TODO: Implement findNodeAggregateByIdentifier() method.
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate {
        // TODO: Implement findParentNodeAggregateByChildOriginDimensionSpacePoint() method.
    }

    public function findParentNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): array {
        // TODO: Implement findParentNodeAggregates() method.
    }

    public function findChildNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): array {
        // TODO: Implement findChildNodeAggregates() method.
    }

    public function findChildNodeAggregatesByName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $name
    ): array {
        // TODO: Implement findChildNodeAggregatesByName() method.
    }

    public function findTetheredChildNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): array {
        // TODO: Implement findTetheredChildNodeAggregates() method.
    }

    public function getDimensionSpacePointsOccupiedByChildNodeName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeName $nodeName,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        OriginDimensionSpacePoint $parentNodeOriginOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet {
        // TODO: Implement getDimensionSpacePointsOccupiedByChildNodeName() method.
    }

    public function countNodes(): int
    {
        // TODO: Implement countNodes() method.
    }

    public function findProjectedContentStreamIdentifiers(): array
    {
        // TODO: Implement findProjectedContentStreamIdentifiers() method.
    }

    public function findProjectedDimensionSpacePoints(): DimensionSpacePointSet
    {
        // TODO: Implement findProjectedDimensionSpacePoints() method.
    }

    public function findProjectedNodeAggregateIdentifiersInContentStream(
        ContentStreamIdentifier $contentStreamIdentifier
    ): array {
        // TODO: Implement findProjectedNodeAggregateIdentifiersInContentStream() method.
    }

    public function findProjectedNodeTypes(): iterable
    {
        // TODO: Implement findProjectedNodeTypes() method.
    }

    public function enableCache(): void
    {
        // TODO: Implement enableCache() method.
    }

    public function disableCache(): void
    {
        // TODO: Implement disableCache() method.
    }
}
