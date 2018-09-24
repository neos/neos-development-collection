<?php
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
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\EventSourcedContentRepository\Domain;

/**
 * The interface to be implemented by content graphs
 */
interface ContentGraphInterface
{
    /**
     * @param \Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return ContentSubgraphInterface|null
     */
    public function getSubgraphByIdentifier(
        Domain\Context\ContentStream\ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): ?ContentSubgraphInterface;

    /**
     * @return array|ContentSubgraphInterface[]
     */
    public function getSubgraphs(): array;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByIdentifierInContentStream(ContentStreamIdentifier $contentStreamIdentifier, NodeIdentifier $nodeIdentifier): ?NodeInterface;

    /**
     * @param Domain\ValueObject\NodeTypeName $nodeTypeName
     * @return NodeInterface|null
     */
    public function findRootNodeByType(Domain\ValueObject\NodeTypeName $nodeTypeName): ?NodeInterface;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePointSet $dimensionSpacePointSet
     * @return array|NodeInterface[]
     */
    public function findNodesByNodeAggregateIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePointSet $dimensionSpacePointSet = null
    ): array;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return NodeAggregate|null
     * @throws Domain\Context\Node\NodeAggregatesTypeIsAmbiguous
     */
    public function findNodeAggregateByIdentifier(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier): ?NodeAggregate;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return array|NodeAggregate[]
     */
    public function findParentAggregates(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier): array;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return array|NodeAggregate[]
     */
    public function findChildAggregates(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier): array;

    /**
     * @param Domain\Context\Node\ReadOnlyNodeInterface $node
     * @return DimensionSpacePointSet
     */
    public function findVisibleDimensionSpacePointsOfNode(Domain\Context\Node\ReadOnlyNodeInterface $node): DimensionSpacePointSet;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return DimensionSpacePointSet
     */
    public function findVisibleDimensionSpacePointsOfNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): DimensionSpacePointSet;

    public function resetCache();
}
