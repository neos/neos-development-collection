<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command;

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

/**
 * Create a specialization of a node in a content stream
 *
 * Copy a node identified by node aggregate identifier and source dimension space point to a specialized dimension space point in a content stream
 * respecting further specialization mechanisms.
 */
final class CreateNodeSpecialization
{
    /**
     * @var \Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var \Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier
     */
    protected $nodeAggregateIdentifier;

    /**
     * @var Domain\ValueObject\DimensionSpacePoint
     */
    protected $sourceDimensionSpacePoint;

    /**
     * @var Domain\ValueObject\DimensionSpacePoint
     */
    protected $targetDimensionSpacePoint;

    /**
     * @var Domain\ValueObject\NodeIdentifier
     */
    protected $specializationIdentifier;


    /**
     * @param \Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier $contentStreamIdentifier
     * @param \Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param Domain\ValueObject\DimensionSpacePoint $sourceDimensionSpacePoint
     * @param Domain\ValueObject\DimensionSpacePoint $targetDimensionSpacePoint
     * @param Domain\ValueObject\NodeIdentifier $specializationIdentifier
     */
    public function __construct(
        Domain\Context\ContentStream\ContentStreamIdentifier $contentStreamIdentifier,
        Domain\Context\NodeAggregate\NodeAggregateIdentifier $nodeAggregateIdentifier,
        Domain\ValueObject\DimensionSpacePoint $sourceDimensionSpacePoint,
        Domain\ValueObject\DimensionSpacePoint $targetDimensionSpacePoint,
        Domain\ValueObject\NodeIdentifier $specializationIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->sourceDimensionSpacePoint = $sourceDimensionSpacePoint;
        $this->targetDimensionSpacePoint = $targetDimensionSpacePoint;
        $this->specializationIdentifier = $specializationIdentifier;
    }

    /**
     * @return \Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): Domain\Context\ContentStream\ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return \Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier
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
     * @return Domain\ValueObject\DimensionSpacePoint
     */
    public function getTargetDimensionSpacePoint(): Domain\ValueObject\DimensionSpacePoint
    {
        return $this->targetDimensionSpacePoint;
    }

    /**
     * @return Domain\ValueObject\NodeIdentifier
     */
    public function getSpecializationIdentifier(): Domain\ValueObject\NodeIdentifier
    {
        return $this->specializationIdentifier;
    }
}
