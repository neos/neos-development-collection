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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Migration\Filters\NodeName;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\CopyableAcrossContentStreamsInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcing\Event\EventInterface;

/**
 * A root node aggregate and its node were created
 */
final class RootNodeAggregateWithNodeWasCreated implements EventInterface, CopyableAcrossContentStreamsInterface
{
    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    private $nodeAggregateIdentifier;

    /**
     * @var NodeTypeName
     */
    protected $nodeTypeName;

    /**
     * Root nodes are by definition visible in *all* dimension space points; so we need to include the full list here.
     *
     * @var DimensionSpacePointSet
     */
    private $visibleInDimensionSpacePoints;

    /**
     * @var UserIdentifier
     */
    private $initiatingUserIdentifier;

    /**
     * @var NodeName
     */
    private $nodeName;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeIdentifier,
        NodeTypeName $nodeTypeName,
        DimensionSpacePointSet $visibleInDimensionSpacePoints,
        UserIdentifier $initiatingUserIdentifier,
        NodeName $nodeName = null
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeIdentifier;
        $this->nodeTypeName = $nodeTypeName;
        $this->visibleInDimensionSpacePoints = $visibleInDimensionSpacePoints;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
        $this->nodeName = $nodeName;
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getNodeTypeName(): NodeTypeName
    {
        return $this->nodeTypeName;
    }

    public function getVisibleInDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->visibleInDimensionSpacePoints;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    public function getNodeName(): ?NodeName
    {
        return $this->nodeName;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream): RootNodeAggregateWithNodeWasCreated
    {
        return new RootNodeAggregateWithNodeWasCreated(
            $targetContentStream,
            $this->nodeAggregateIdentifier,
            $this->nodeTypeName,
            $this->visibleInDimensionSpacePoints,
            $this->initiatingUserIdentifier,
            $this->nodeName
        );
    }
}
