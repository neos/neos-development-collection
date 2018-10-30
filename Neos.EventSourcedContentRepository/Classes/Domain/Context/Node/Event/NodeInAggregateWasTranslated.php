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

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\EventSourcing\Event\EventInterface;

class NodeInAggregateWasTranslated implements EventInterface, CopyableAcrossContentStreamsInterface
{

    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeIdentifier
     */
    private $sourceNodeIdentifier;

    /**
     * Node identifier for the translated node
     *
     * @var NodeIdentifier
     */
    private $destinationNodeIdentifier;

    /**
     * Node identifier of the parent node for the translated node
     *
     * @var NodeIdentifier
     */
    private $destinationParentNodeIdentifier;

    /**
     * Dimension space point for the translated node
     *
     * @var DimensionSpacePoint
     */
    private $dimensionSpacePoint;

    /**
     * Visibility of node in the dimension space
     *
     * @var DimensionSpacePointSet
     */
    private $visibleInDimensionSpacePoints;

    /**
     * NodeInAggregateWasTranslated constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $sourceNodeIdentifier
     * @param NodeIdentifier $destinationNodeIdentifier
     * @param NodeIdentifier $destinationParentNodeIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param DimensionSpacePointSet $visibleInDimensionSpacePoints
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeIdentifier $sourceNodeIdentifier,
        NodeIdentifier $destinationNodeIdentifier,
        NodeIdentifier $destinationParentNodeIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        DimensionSpacePointSet $visibleInDimensionSpacePoints
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->sourceNodeIdentifier = $sourceNodeIdentifier;
        $this->destinationNodeIdentifier = $destinationNodeIdentifier;
        $this->destinationParentNodeIdentifier = $destinationParentNodeIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->visibleInDimensionSpacePoints = $visibleInDimensionSpacePoints;
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
    public function getSourceNodeIdentifier(): NodeIdentifier
    {
        return $this->sourceNodeIdentifier;
    }

    /**
     * @return NodeIdentifier
     */
    public function getDestinationNodeIdentifier(): NodeIdentifier
    {
        return $this->destinationNodeIdentifier;
    }

    /**
     * @return NodeIdentifier
     */
    public function getDestinationParentNodeIdentifier(): NodeIdentifier
    {
        return $this->destinationParentNodeIdentifier;
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    /**
     * @return DimensionSpacePointSet
     */
    public function getVisibleInDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->visibleInDimensionSpacePoints;
    }

    /**
     * @param ContentStreamIdentifier $targetContentStream
     * @return NodeInAggregateWasTranslated
     */
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream)
    {
        return new NodeInAggregateWasTranslated(
            $targetContentStream,
            $this->sourceNodeIdentifier,
            $this->destinationNodeIdentifier,
            $this->destinationParentNodeIdentifier,
            $this->dimensionSpacePoint,
            $this->visibleInDimensionSpacePoints
        );
    }
}
