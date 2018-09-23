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

use Neos\EventSourcedContentRepository\Domain;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\CopyableAcrossContentStreamsInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;
use Neos\EventSourcing\Event\EventInterface;

/**
 * A node generalization was created
 */
final class NodeGeneralizationWasCreated implements EventInterface, CopyableAcrossContentStreamsInterface
{
    /**
     * @var ContentStream\ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var NodeAggregate\NodeAggregateIdentifier
     */
    protected $nodeAggregateIdentifier;

    /**
     * @var Domain\ValueObject\DimensionSpacePoint
     */
    protected $sourceDimensionSpacePoint;

    /**
     * @var Domain\ValueObject\NodeIdentifier
     */
    protected $generalizationIdentifier;

    /**
     * @var Domain\ValueObject\DimensionSpacePoint
     */
    protected $generalizationLocation;

    /**
     * @var Domain\ValueObject\DimensionSpacePointSet
     */
    protected $generalizationVisibility;


    /**
     * @param ContentStream\ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregate\NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param Domain\ValueObject\DimensionSpacePoint $sourceDimensionSpacePoint
     * @param Domain\ValueObject\NodeIdentifier $generalizationIdentifier
     * @param Domain\ValueObject\DimensionSpacePoint $generalizationLocation
     * @param Domain\ValueObject\DimensionSpacePointSet $generalizationVisibility
     */
    public function __construct(
        ContentStream\ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregate\NodeAggregateIdentifier $nodeAggregateIdentifier,
        Domain\ValueObject\DimensionSpacePoint $sourceDimensionSpacePoint,
        Domain\ValueObject\NodeIdentifier $generalizationIdentifier,
        Domain\ValueObject\DimensionSpacePoint $generalizationLocation,
        Domain\ValueObject\DimensionSpacePointSet $generalizationVisibility
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->sourceDimensionSpacePoint = $sourceDimensionSpacePoint;
        $this->generalizationIdentifier = $generalizationIdentifier;
        $this->generalizationLocation = $generalizationLocation;
        $this->generalizationVisibility = $generalizationVisibility;
    }


    /**
     * @return ContentStream\ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStream\ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return NodeAggregate\NodeAggregateIdentifier
     */
    public function getNodeAggregateIdentifier(): NodeAggregate\NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    /**
     * @return Domain\ValueObject\DimensionSpacePoint
     */
    public function getSourceDimensionSpacePoint(): Domain\ValueObject\DimensionSpacePoint
    {
        return $this->sourceDimensionSpacePoint;
    }

    /**
     * @return Domain\ValueObject\NodeIdentifier
     */
    public function getGeneralizationIdentifier(): Domain\ValueObject\NodeIdentifier
    {
        return $this->generalizationIdentifier;
    }

    /**
     * @return Domain\ValueObject\DimensionSpacePoint
     */
    public function getGeneralizationLocation(): Domain\ValueObject\DimensionSpacePoint
    {
        return $this->generalizationLocation;
    }

    /**
     * @return Domain\ValueObject\DimensionSpacePointSet
     */
    public function getGeneralizationVisibility(): Domain\ValueObject\DimensionSpacePointSet
    {
        return $this->generalizationVisibility;
    }


    /**
     * @param ContentStream\ContentStreamIdentifier $targetContentStream
     * @return NodeGeneralizationWasCreated
     */
    public function createCopyForContentStream(ContentStream\ContentStreamIdentifier $targetContentStream): NodeGeneralizationWasCreated
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
