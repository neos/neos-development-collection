<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\ValueObject;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * Nodes were moved
 *
 * @Flow\Proxy(false)
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

    public static function fromArray(array $array): self
    {
        return new static(
            NodeIdentifier::fromString($array['nodeIdentifier']),
            isset($array['newParentNodeIdentifier']) ? NodeIdentifier::fromString($array['newParentNodeIdentifier']) : null,
            isset($array['newSucceedingSiblingIdentifier']) ? NodeIdentifier::fromString($array['newSucceedingSiblingIdentifier']) : null,
            new DimensionSpacePointSet($array['dimensionSpacePointSet'])
        );
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
    public function getNewParentNodeIdentifier(): ?NodeIdentifier
    {
        return $this->newParentNodeIdentifier;
    }

    /**
     * @return NodeIdentifier
     */
    public function getNewSucceedingSiblingIdentifier(): ?NodeIdentifier
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
