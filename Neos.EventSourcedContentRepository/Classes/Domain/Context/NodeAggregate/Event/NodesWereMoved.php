<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeMoveMappings;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\Domain\Context\Node\CopyableAcrossContentStreamsInterface;

/**
 * Nodes of a node aggregate were moved in a content stream as defined in the node move mappings
 *
 * @Flow\Proxy(false)
 */
final class NodesWereMoved implements DomainEventInterface, CopyableAcrossContentStreamsInterface
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
     * @var NodeAggregateIdentifier|null
     */
    private $newParentNodeAggregateIdentifier;

    /**
     * @var NodeAggregateIdentifier|null
     */
    private $newSucceedingSiblingNodeAggregateIdentifier;

    /**
     * @var NodeMoveMappings|null
     */
    private $nodeMoveMappings;

    /**
     * @var DimensionSpacePointSet
     */
    private $affectedDimensionSpacePoints;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        ?NodeAggregateIdentifier $newParentNodeAggregateIdentifier,
        ?NodeAggregateIdentifier $newSucceedingSiblingNodeAggregateIdentifier,
        ?NodeMoveMappings $nodeMoveMappings,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->newParentNodeAggregateIdentifier = $newParentNodeAggregateIdentifier;
        $this->newSucceedingSiblingNodeAggregateIdentifier = $newSucceedingSiblingNodeAggregateIdentifier;
        $this->nodeMoveMappings = $nodeMoveMappings;
        $this->affectedDimensionSpacePoints = $affectedDimensionSpacePoints;
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getNewParentNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->newParentNodeAggregateIdentifier;
    }

    public function getNewSucceedingSiblingNodeAggregateIdentifier(): ?NodeAggregateIdentifier
    {
        return $this->newSucceedingSiblingNodeAggregateIdentifier;
    }

    public function getNodeMoveMappings(): ?NodeMoveMappings
    {
        return $this->nodeMoveMappings;
    }

    public function getAffectedDimensionSpacePoints(): DimensionSpacePointSet
    {
        return $this->affectedDimensionSpacePoints;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): NodesWereMoved
    {
        return new NodesWereMoved(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->newParentNodeAggregateIdentifier,
            $this->newSucceedingSiblingNodeAggregateIdentifier,
            $this->nodeMoveMappings,
            $this->affectedDimensionSpacePoints
        );
    }
}
