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
 * A node specialization was created
 */
final class NodeSpecializationWasCreated implements EventInterface, CopyableAcrossContentStreamsInterface
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
    protected $specializationIdentifier;

    /**
     * @var DimensionSpacePoint
     */
    protected $specializationLocation;

    /**
     * @var DimensionSpacePointSet
     */
    protected $specializationVisibility;


    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePoint $sourceDimensionSpacePoint
     * @param NodeIdentifier $specializationIdentifier
     * @param DimensionSpacePoint $specializationLocation
     * @param DimensionSpacePointSet $specializationVisibility
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePoint $sourceDimensionSpacePoint,
        NodeIdentifier $specializationIdentifier,
        DimensionSpacePoint $specializationLocation,
        DimensionSpacePointSet $specializationVisibility
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->sourceDimensionSpacePoint = $sourceDimensionSpacePoint;
        $this->specializationIdentifier = $specializationIdentifier;
        $this->specializationLocation = $specializationLocation;
        $this->specializationVisibility = $specializationVisibility;
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
    public function getSpecializationIdentifier(): NodeIdentifier
    {
        return $this->specializationIdentifier;
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getSpecializationLocation(): DimensionSpacePoint
    {
        return $this->specializationLocation;
    }

    /**
     * @return DimensionSpacePointSet
     */
    public function getSpecializationVisibility(): DimensionSpacePointSet
    {
        return $this->specializationVisibility;
    }


    /**
     * @param ContentStreamIdentifier $targetContentStream
     * @return NodeSpecializationWasCreated
     */
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream): NodeSpecializationWasCreated
    {
        return new NodeSpecializationWasCreated(
            $targetContentStream,
            $this->nodeAggregateIdentifier,
            $this->sourceDimensionSpacePoint,
            $this->specializationIdentifier,
            $this->specializationLocation,
            $this->specializationVisibility
        );
    }
}
