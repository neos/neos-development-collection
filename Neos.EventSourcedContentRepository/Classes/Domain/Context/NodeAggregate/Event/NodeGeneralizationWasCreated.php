<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\CopyableAcrossContentStreamsInterface;
use Neos\EventSourcing\Event\EventInterface;

/**
 * A node generalization was created
 */
final class NodeGeneralizationWasCreated implements EventInterface, CopyableAcrossContentStreamsInterface
{
    /**
     * @var ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    protected $nodeAggregateIdentifier;

    /**
     * @var DimensionSpacePoint
     */
    protected $sourceDimensionSpacePoint;

    /**
     * @var NodeIdentifier
     */
    protected $generalizationIdentifier;

    /**
     * @var DimensionSpacePoint
     */
    protected $generalizationLocation;

    /**
     * @var DimensionSpacePointSet
     */
    protected $generalizationVisibility;


    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePoint $sourceDimensionSpacePoint
     * @param NodeIdentifier $generalizationIdentifier
     * @param DimensionSpacePoint $generalizationLocation
     * @param DimensionSpacePointSet $generalizationVisibility
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePoint $sourceDimensionSpacePoint,
        NodeIdentifier $generalizationIdentifier,
        DimensionSpacePoint $generalizationLocation,
        DimensionSpacePointSet $generalizationVisibility
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->sourceDimensionSpacePoint = $sourceDimensionSpacePoint;
        $this->generalizationIdentifier = $generalizationIdentifier;
        $this->generalizationLocation = $generalizationLocation;
        $this->generalizationVisibility = $generalizationVisibility;
    }


    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return NodeAggregateIdentifier
     */
    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getSourceDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->sourceDimensionSpacePoint;
    }

    /**
     * @return NodeIdentifier
     */
    public function getGeneralizationIdentifier(): NodeIdentifier
    {
        return $this->generalizationIdentifier;
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getGeneralizationLocation(): DimensionSpacePoint
    {
        return $this->generalizationLocation;
    }

    /**
     * @return DimensionSpacePointSet
     */
    public function getGeneralizationVisibility(): DimensionSpacePointSet
    {
        return $this->generalizationVisibility;
    }


    /**
     * @param ContentStreamIdentifier $targetContentStream
     * @return NodeGeneralizationWasCreated
     */
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream): NodeGeneralizationWasCreated
    {
        return new NodeGeneralizationWasCreated(
            $targetContentStream,
            $this->nodeAggregateIdentifier,
            $this->sourceDimensionSpacePoint,
            $this->generalizationIdentifier,
            $this->generalizationLocation,
            $this->generalizationVisibility
        );
    }
}
