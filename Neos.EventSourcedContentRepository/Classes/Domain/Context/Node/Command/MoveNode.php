<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\Node\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\Node\RelationDistributionStrategy;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;

/**
 * Move node command
 *
 * In `contentStreamIdentifier`
 * and `dimensionSpacePoint`,
 * move node `nodeAggregateIdentifier`
 * into `newParentNodeAggregateIdentifier` (or keep the current parent)
 * before `newSucceedingSiblingIdentifier` (or as last of all siblings)
 * using `relationDistributionStrategy`
 *
 * This is only allowed if both nodes exist and the new parent aggregate's type allows children of the given aggregate's type
 */
final class MoveNode
{
    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var DimensionSpacePoint
     */
    private $dimensionSpacePoint;

    /**
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $newParentNodeAggregateIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $newSucceedingSiblingNodeAggregateIdentifier;

    /**
     * @var RelationDistributionStrategy
     */
    private $relationDistributionStrategy;


    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param NodeAggregateIdentifier|null $newParentNodeAggregateIdentifier
     * @param NodeAggregateIdentifier|null $newSucceedingSiblingNodeAggregateIdentifier
     * @param RelationDistributionStrategy $relationDistributionStrategy
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        ?NodeAggregateIdentifier $newParentNodeAggregateIdentifier,
        ?NodeAggregateIdentifier $newSucceedingSiblingNodeAggregateIdentifier,
        RelationDistributionStrategy $relationDistributionStrategy
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->newParentNodeAggregateIdentifier = $newParentNodeAggregateIdentifier;
        $this->newSucceedingSiblingNodeAggregateIdentifier = $newSucceedingSiblingNodeAggregateIdentifier;
        $this->relationDistributionStrategy = $relationDistributionStrategy;
    }


    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier|null
     */
    public function getNewParentNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->newParentNodeAggregateIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier|null
     */
    public function getNewSucceedingSiblingNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->newSucceedingSiblingNodeAggregateIdentifier;
    }

    /**
     * @return RelationDistributionStrategy
     */
    public function getRelationDistributionStrategy(): RelationDistributionStrategy
    {
        return $this->relationDistributionStrategy;
    }
}
