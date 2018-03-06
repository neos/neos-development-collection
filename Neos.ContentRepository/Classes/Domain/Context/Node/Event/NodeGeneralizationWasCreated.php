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
 * A node generalization was created
 */
final class NodeGeneralizationWasCreated implements EventInterface, CopyableAcrossContentStreamsInterface
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
     * @var Domain\ValueObject\DimensionSpacePoint
     */
    protected $sourceLocation;

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
     * @param Domain\ValueObject\ContentStreamIdentifier $contentStreamIdentifier
     * @param Domain\ValueObject\NodeIdentifier $nodeIdentifier
     * @param Domain\ValueObject\DimensionSpacePoint $sourceLocation
     * @param Domain\ValueObject\NodeIdentifier $generalizationIdentifier
     * @param Domain\ValueObject\DimensionSpacePoint $generalizationLocation
     * @param Domain\ValueObject\DimensionSpacePointSet $generalizationVisibility
     */
    public function __construct(
        Domain\ValueObject\ContentStreamIdentifier $contentStreamIdentifier,
        Domain\ValueObject\NodeIdentifier $nodeIdentifier,
        Domain\ValueObject\DimensionSpacePoint $sourceLocation,
        Domain\ValueObject\NodeIdentifier $generalizationIdentifier,
        Domain\ValueObject\DimensionSpacePoint $generalizationLocation,
        Domain\ValueObject\DimensionSpacePointSet $generalizationVisibility
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->sourceLocation = $sourceLocation;
        $this->generalizationIdentifier = $generalizationIdentifier;
        $this->generalizationLocation = $generalizationLocation;
        $this->generalizationVisibility = $generalizationVisibility;
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
     * @return Domain\ValueObject\DimensionSpacePoint
     */
    public function getSourceLocation(): Domain\ValueObject\DimensionSpacePoint
    {
        return $this->sourceLocation;
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
     * @param Domain\ValueObject\ContentStreamIdentifier $targetContentStream
     * @return NodeGeneralizationWasCreated
     */
    public function createCopyForContentStream(Domain\ValueObject\ContentStreamIdentifier $targetContentStream): NodeGeneralizationWasCreated
    {
        return new NodeGeneralizationWasCreated(
            $targetContentStream,
            $this->nodeIdentifier,
            $this->sourceLocation,
            $this->generalizationIdentifier,
            $this->generalizationLocation,
            $this->generalizationVisibility
        );
    }
}
