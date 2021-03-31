<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeMoveMappings;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * A node aggregate was moved in a content stream as defined in the node move mappings.
 *
 * We always move a node aggregate (in a given ContenStreamIdentifier, identified by a NodeAggregateIdentifier).
 *
 * You can move any amount of nodes in the aggregate. The targets (new parents // new succeeding) for each node & dimension space point
 * are specified in {@see NodeMoveMappings}.
 *
 * @Flow\Proxy(false)
 */
final class NodeAggregateWasMoved implements DomainEventInterface, PublishableToOtherContentStreamsInterface
{
    private ContentStreamIdentifier $contentStreamIdentifier;

    private NodeAggregateIdentifier $nodeAggregateIdentifier;

    /**
     * The MoveNodeMappings contains for every OriginDimensionSpacePoint of the aggregate which should be moved,
     * a list of new parent NodeAggregateIdentifiers, and a list of new succeeding-sibling NodeAggregateIdentifiers.
     *
     * This happens between
     *
     * @var NodeMoveMappings|null
     */
    private ?NodeMoveMappings $nodeMoveMappings;

    /**
     * This specifies all "edges" which should move to the END of their siblings. All dimension space points included here
     * must NOT be part of any MoveNodeMapping.
     *
     * This case is needed because we can only specify a new parent or/and a new succeeding sibling in the
     * MoveNodeMappings; so we need a way to "move to the end".
     *
     * @var DimensionSpacePointSet
     */
    private DimensionSpacePointSet $repositionNodesWithoutAssignments;

    private UserIdentifier $initiatingUserIdentifier;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        ?NodeMoveMappings $nodeMoveMappings,
        DimensionSpacePointSet $repositionNodesWithoutAssignments,
        UserIdentifier $initiatingUserIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->nodeMoveMappings = $nodeMoveMappings;
        $this->repositionNodesWithoutAssignments = $repositionNodesWithoutAssignments;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifier;
    }

    public function getNodeMoveMappings(): ?NodeMoveMappings
    {
        return $this->nodeMoveMappings;
    }

    public function getRepositionNodesWithoutAssignments(): DimensionSpacePointSet
    {
        return $this->repositionNodesWithoutAssignments;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): NodeAggregateWasMoved
    {
        return new NodeAggregateWasMoved(
            $targetContentStreamIdentifier,
            $this->nodeAggregateIdentifier,
            $this->nodeMoveMappings,
            $this->repositionNodesWithoutAssignments,
            $this->initiatingUserIdentifier
        );
    }
}
