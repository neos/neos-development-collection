<?php
namespace Neos\ContentRepository\Domain\Context\Node\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain;
use Neos\EventSourcing\Event\EventInterface;

/**
 * A node specialization was created
 */
final class NodeSpecializationWasCreated implements EventInterface, CopyableAcrossContentStreamsInterface
{
    /**
     * @var \Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var \Neos\ContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier
     */
    protected $nodeAggregateIdentifier;

    /**
     * @var Domain\ValueObject\DimensionSpacePoint
     */
    protected $sourceDimensionSpacePoint;

    /**
     * @var Domain\ValueObject\NodeIdentifier
     */
    protected $specializationIdentifier;

    /**
     * @var Domain\ValueObject\DimensionSpacePoint
     */
    protected $specializationLocation;

    /**
     * @var Domain\ValueObject\DimensionSpacePointSet
     */
    protected $specializationVisibility;


    /**
     * @param \Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier $contentStreamIdentifier
     * @param \Neos\ContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param Domain\ValueObject\DimensionSpacePoint $sourceDimensionSpacePoint
     * @param Domain\ValueObject\NodeIdentifier $specializationIdentifier
     * @param Domain\ValueObject\DimensionSpacePoint $specializationLocation
     * @param Domain\ValueObject\DimensionSpacePointSet $specializationVisibility
     */
    public function __construct(
        Domain\Context\ContentStream\ContentStreamIdentifier $contentStreamIdentifier,
        Domain\Context\NodeAggregate\NodeAggregateIdentifier $nodeAggregateIdentifier,
        Domain\ValueObject\DimensionSpacePoint $sourceDimensionSpacePoint,
        Domain\ValueObject\NodeIdentifier $specializationIdentifier,
        Domain\ValueObject\DimensionSpacePoint $specializationLocation,
        Domain\ValueObject\DimensionSpacePointSet $specializationVisibility
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->sourceDimensionSpacePoint = $sourceDimensionSpacePoint;
        $this->specializationIdentifier = $specializationIdentifier;
        $this->specializationLocation = $specializationLocation;
        $this->specializationVisibility = $specializationVisibility;
    }


    /**
     * @return \Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): Domain\Context\ContentStream\ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return \Neos\ContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier
     */
    public function getNodeAggregateIdentifier(): Domain\Context\NodeAggregate\NodeAggregateIdentifier
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
    public function getSpecializationIdentifier(): Domain\ValueObject\NodeIdentifier
    {
        return $this->specializationIdentifier;
    }

    /**
     * @return Domain\ValueObject\DimensionSpacePoint
     */
    public function getSpecializationLocation(): Domain\ValueObject\DimensionSpacePoint
    {
        return $this->specializationLocation;
    }

    /**
     * @return Domain\ValueObject\DimensionSpacePointSet
     */
    public function getSpecializationVisibility(): Domain\ValueObject\DimensionSpacePointSet
    {
        return $this->specializationVisibility;
    }


    /**
     * @param \Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier $targetContentStream
     * @return NodeSpecializationWasCreated
     */
    public function createCopyForContentStream(Domain\Context\ContentStream\ContentStreamIdentifier $targetContentStream): NodeSpecializationWasCreated
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
