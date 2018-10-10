<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\Node\Event;

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
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcing\Event\EventInterface;

/**
 * Root node was created event
 */
final class RootNodeWasCreated implements EventInterface, CopyableAcrossContentStreamsInterface
{
    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeIdentifier
     */
    private $nodeIdentifier;

    /**
     * @var NodeTypeName
     */
    protected $nodeTypeName;

    /**
     * the root node is by definition visible in *all* dimension space points; so we need to include the full list here.
     *
     * @var DimensionSpacePointSet
     */
    private $visibleDimensionSpacePoints;

    /**
     * @var UserIdentifier
     */
    private $initiatingUserIdentifier;

    /**
     * RootNodeWasCreated constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @param NodeTypeName $nodeTypeName
     * @param DimensionSpacePointSet $visibleDimensionSpacePoints
     * @param UserIdentifier $initiatingUserIdentifier
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, NodeIdentifier $nodeIdentifier, NodeTypeName $nodeTypeName, DimensionSpacePointSet $visibleDimensionSpacePoints, UserIdentifier $initiatingUserIdentifier)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->nodeTypeName = $nodeTypeName;
        $this->visibleDimensionSpacePoints = $visibleDimensionSpacePoints;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }


    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return NodeIdentifier
     */
    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
    }

    /**
     * Getter for NodeTypeName
     *
     * @return NodeTypeName
     */
    public function getNodeTypeName(): NodeTypeName
    {
        return $this->nodeTypeName;
    }

    /**
     * @return DimensionSpacePointSet
     */
    public function getVisibleDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->visibleDimensionSpacePoints;
    }

    /**
     * @return UserIdentifier
     */
    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    /**
     * @param ContentStreamIdentifier $targetContentStream
     * @return RootNodeWasCreated
     */
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream)
    {
        return new RootNodeWasCreated(
            $targetContentStream,
            $this->nodeIdentifier,
            $this->nodeTypeName,
            $this->visibleDimensionSpacePoints,
            $this->initiatingUserIdentifier
        );
    }
}
