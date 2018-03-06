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
 * A node was specialized
 */
final class NodeSpecializationWasCreated implements EventInterface, CopyableAcrossContentStreamsInterface
{
    /**
     * @var Domain\ValueObject\ContentStreamIdentifier
     */
    protected $contentStreamIdentifier;

    /**
     * @var Domain\ValueObject\NodeIdentifier
     */
    protected $nodeIdentifier;

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
     * @param Domain\ValueObject\ContentStreamIdentifier $contentStreamIdentifier
     * @param Domain\ValueObject\NodeIdentifier $nodeIdentifier
     * @param Domain\ValueObject\NodeIdentifier $specializationIdentifier
     * @param Domain\ValueObject\DimensionSpacePoint $specializationLocation
     * @param Domain\ValueObject\DimensionSpacePointSet $specializationVisibility
     */
    public function __construct(
        Domain\ValueObject\ContentStreamIdentifier $contentStreamIdentifier,
        Domain\ValueObject\NodeIdentifier $nodeIdentifier,
        Domain\ValueObject\NodeIdentifier $specializationIdentifier,
        Domain\ValueObject\DimensionSpacePoint $specializationLocation,
        Domain\ValueObject\DimensionSpacePointSet $specializationVisibility
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->specializationIdentifier = $specializationIdentifier;
        $this->specializationLocation = $specializationLocation;
        $this->specializationVisibility = $specializationVisibility;
    }


    /**
     * @return Domain\ValueObject\ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): Domain\ValueObject\ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return Domain\ValueObject\NodeIdentifier
     */
    public function getNodeIdentifier(): Domain\ValueObject\NodeIdentifier
    {
        return $this->nodeIdentifier;
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
     * @param Domain\ValueObject\ContentStreamIdentifier $targetContentStream
     * @return NodeSpecializationWasCreated
     */
    public function createCopyForContentStream(Domain\ValueObject\ContentStreamIdentifier $targetContentStream): NodeSpecializationWasCreated
    {
        return new NodeSpecializationWasCreated(
            $targetContentStream,
            $this->nodeIdentifier,
            $this->specializationIdentifier,
            $this->specializationLocation,
            $this->specializationVisibility
        );
    }
}
