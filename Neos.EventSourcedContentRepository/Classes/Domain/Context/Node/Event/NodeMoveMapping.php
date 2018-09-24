<?php

namespace Neos\EventSourcedContentRepository\Domain\Context\Node\Event;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeIdentifier;

/**
 * Nodes were moved
 */
final class NodeMoveMapping
{
    /**
     * @var NodeIdentifier
     */
    private $nodeIdentifier;

    /**
     * @var NodeIdentifier
     */
    private $newParentNodeIdentifier;

    /**
     * @var NodeIdentifier
     */
    private $newSucceedingSiblingIdentifier;

    /**
     * @var DimensionSpacePointSet
     */
    private $dimensionSpacePointSet;


    /**
     * @param NodeIdentifier $nodeIdentifier
     * @param NodeIdentifier|null $newParentNodeIdentifier
     * @param NodeIdentifier|null $newSucceedingSiblingIdentifier
     * @param DimensionSpacePointSet $dimensionSpacePointSet
     */
    public function __construct(NodeIdentifier $nodeIdentifier, ?NodeIdentifier $newParentNodeIdentifier, ?NodeIdentifier $newSucceedingSiblingIdentifier, DimensionSpacePointSet $dimensionSpacePointSet)
    {
        $this->nodeIdentifier = $nodeIdentifier;
        $this->newParentNodeIdentifier = $newParentNodeIdentifier;
        $this->newSucceedingSiblingIdentifier = $newSucceedingSiblingIdentifier;
        $this->dimensionSpacePointSet = $dimensionSpacePointSet;
    }


    /**
     * @return NodeIdentifier
     */
    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
    }

    /**
     * @return NodeIdentifier
     */
    public function getNewParentNodeIdentifier(): NodeIdentifier
    {
        return $this->newParentNodeIdentifier;
    }

    /**
     * @return NodeIdentifier
     */
    public function getNewSucceedingSiblingIdentifier(): NodeIdentifier
    {
        return $this->newSucceedingSiblingIdentifier;
    }

    /**
     * @return DimensionSpacePointSet
     */
    public function getDimensionSpacePointSet(): DimensionSpacePointSet
    {
        return $this->dimensionSpacePointSet;
    }
}
