<?php
namespace Neos\ContentRepository\Domain\Context\Node\Event;

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\EventSourcing\Event\EventInterface;

class NodeWasTranslatedInAggregate implements EventInterface
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
    private $visibleDimensionSpacePoints;

    /**
     * NodeWasTranslatedInAggregate constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $sourceNodeIdentifier
     * @param NodeIdentifier $destinationNodeIdentifier
     * @param NodeIdentifier $destinationParentNodeIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param DimensionSpacePointSet $visibleDimensionSpacePoints
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeIdentifier $sourceNodeIdentifier,
        NodeIdentifier $destinationNodeIdentifier,
        NodeIdentifier $destinationParentNodeIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        DimensionSpacePointSet $visibleDimensionSpacePoints
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->sourceNodeIdentifier = $sourceNodeIdentifier;
        $this->destinationNodeIdentifier = $destinationNodeIdentifier;
        $this->destinationParentNodeIdentifier = $destinationParentNodeIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->visibleDimensionSpacePoints = $visibleDimensionSpacePoints;
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
    public function getVisibleDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->visibleDimensionSpacePoints;
    }

}