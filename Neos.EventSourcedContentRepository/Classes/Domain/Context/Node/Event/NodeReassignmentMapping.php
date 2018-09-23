<?php

namespace Neos\EventSourcedContentRepository\Domain\Context\Node\Event;

use Neos\EventSourcedContentRepository\Domain;

/**
 * The mapping for a node to be reassigned to a new parent or succeeding sibling in a given dimension space point
 */
final class NodeReassignmentMapping
{
    /**
     * @var Domain\ValueObject\NodeIdentifier
     */
    private $nodeIdentifier;

    /**
     * The node's new parent node.
     * If null, the node is to be kept assigned to its current parent
     *
     * @var Domain\ValueObject\NodeIdentifier
     */
    private $newParentNodeIdentifier;

    /**
     * The node's new succeeding sibling node.
     * If noll, the node is to be assigned to the back of the list of its siblings.
     *
     * @var Domain\ValueObject\NodeIdentifier
     */
    private $newSucceedingSiblingIdentifier;

    /**
     * @var Domain\ValueObject\DimensionSpacePoint
     */
    private $dimensionSpacePoint;


    /**
     * @param Domain\ValueObject\NodeIdentifier $nodeIdentifier
     * @param Domain\ValueObject\NodeIdentifier|null $newParentNodeIdentifier
     * @param Domain\ValueObject\NodeIdentifier|null $newSucceedingSiblingIdentifier
     * @param Domain\ValueObject\DimensionSpacePoint $dimensionSpacePoint
     */
    public function __construct(
        Domain\ValueObject\NodeIdentifier $nodeIdentifier,
        ?Domain\ValueObject\NodeIdentifier $newParentNodeIdentifier,
        ?Domain\ValueObject\NodeIdentifier $newSucceedingSiblingIdentifier,
        Domain\ValueObject\DimensionSpacePoint $dimensionSpacePoint
    ) {
        $this->nodeIdentifier = $nodeIdentifier;
        $this->newParentNodeIdentifier = $newParentNodeIdentifier;
        $this->newSucceedingSiblingIdentifier = $newSucceedingSiblingIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
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
    public function getNewParentNodeIdentifier(): Domain\ValueObject\NodeIdentifier
    {
        return $this->newParentNodeIdentifier;
    }

    /**
     * @return Domain\ValueObject\NodeIdentifier
     */
    public function getNewSucceedingSiblingIdentifier(): Domain\ValueObject\NodeIdentifier
    {
        return $this->newSucceedingSiblingIdentifier;
    }

    /**
     * @return Domain\ValueObject\DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): Domain\ValueObject\DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }
}
