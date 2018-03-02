<?php
namespace Neos\ContentRepository\Domain\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain;

/**
 * The interface to be implemented by content graphs
 */
interface ContentGraphInterface
{
    /**
     * @param Domain\ValueObject\ContentStreamIdentifier $contentStreamIdentifier
     * @param Domain\ValueObject\DimensionSpacePoint $dimensionSpacePoint
     * @return ContentSubgraphInterface|null
     */
    public function getSubgraphByIdentifier(
        Domain\ValueObject\ContentStreamIdentifier $contentStreamIdentifier,
        Domain\ValueObject\DimensionSpacePoint $dimensionSpacePoint
    ): ?ContentSubgraphInterface;

    /**
     * @return array|ContentSubgraphInterface[]
     */
    public function getSubgraphs(): array;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @param Domain\Service\Context|null $context
     * @return NodeInterface|null
     */
    public function findNodeByIdentifierInContentStream(ContentStreamIdentifier $contentStreamIdentifier, NodeIdentifier $nodeIdentifier, Domain\Service\Context $context = null): ?NodeInterface;

    /**
     * @param Domain\ValueObject\NodeTypeName $nodeTypeName
     * @return NodeInterface|null
     */
    public function findRootNodeByType(Domain\ValueObject\NodeTypeName $nodeTypeName): ?NodeInterface;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param Domain\ValueObject\DimensionSpacePointSet|null $dimensionSpacePointSet
     * @return array|NodeInterface[]
     */
    public function findNodesByNodeAggregateIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        Domain\ValueObject\DimensionSpacePointSet $dimensionSpacePointSet = null
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
     * @param NodeAggregate $nodeAggregate
     * @return array|NodeAggregate[]
     * @throws Domain\Context\Node\NodeAggregatesTypeIsAmbiguous
     */
    public function findParentAggregates(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregate $nodeAggregate): array;

    /**
     * @param Domain\Context\Node\ReadOnlyNodeInterface $node
     * @return Domain\ValueObject\DimensionSpacePointSet
     */
    public function findVisibleDimensionSpacePointsOfNode(Domain\Context\Node\ReadOnlyNodeInterface $node): Domain\ValueObject\DimensionSpacePointSet;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return Domain\ValueObject\DimensionSpacePointSet
     */
    public function findVisibleDimensionSpacePointsOfNodeAggregate(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier): Domain\ValueObject\DimensionSpacePointSet;
}
