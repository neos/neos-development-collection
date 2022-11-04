<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeMove\Event;

use Neos\ContentRepository\Core\Feature\NodeMove\Dto\OriginNodeMoveMappings;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\EventStore\EventInterface;

/**
 * A node aggregate was moved in a content stream as defined in the node move mappings.
 *
 * We always move a node aggregate identified by a NodeAggregateId (in a given ContentStreamId); or
 * parts of the NodeAggregate.
 *
 * The inner structure of the event is rather complicated, that's why the following picture shows it:
 *
 * ```
 *┌───────────────────────────┐
 *│   NodeAggregateWasMoved   │
 *│-> contains NodeAggregateId│
 *└─────────┬─────────────────┘
 *          │
 *          │   ┌────────────────────────────────────────────────┐
 *          │   │             OriginNodeMoveMapping              │
 *          │  *│     -> contains OriginDimensionSpacePoint      │
 *          └───▶                                                │
 *              │ (1 per OriginDimensionSpacePoint to be moved)  │
 *              └──────┬─────────────────────────────────────────┘
 *                     │
 *                     │   ┌───────────────────────────────────┐
 *                     │   │      CoverageNodeMoveMapping      │   ┌─────────────────────────────┐
 *                     │  *│   -> coveredDimensionSpacePoint   │   │       NoveMoveTarget        │
 *                     └───▶                                   │   │                             │
 *                         │ ?newSucceedingSibling, ?newParent ├───▶  nodeAggregateId            │
 *                         │     (exactly one must be set)     │   │  originDimensionSpacePoint  │
 *                         │                                   │   └─────────────────────────────┘
 *                         │ (1 per coveredDimensionSpacePoint │
 *                         │  to be moved - for each edge in   │
 *                         └───────────────────────────────────┘
 * ```
 *
 * - We move some parts of a single NodeAggregate (`NodeAggregateWasMoved`).
 * - For each OriginDimensionSpacePoint (where a materialized Node exists which should be moved),
 *   an `OriginNodeMoveMapping` exists.
 * - For each Node which we want to move, we need to specify where the *incoming edges* of the node
 *   should be (if they should be moved). For each of these, a `CoverageNodeMoveMapping` exists.
 *
 * ## Specifying the Target of a Move inside `CoverageNodeMoveMapping`
 *
 * For a given `DimensionSpacePoint`, we specify the target node of the move as follows:
 *
 * - If `newSucceedingSibling` is specified, this implicitly sets the target parent node as well,
 *   because the parent node of the `newSucceedingSibling` will be used. This means the `newParent`
 *   will be NULL in this case.
 * - If you want to move something at the END of a children list (or as the first child, when no child
 *   exists yet), you specify `newParent`, but in this case, `newSucceedingSibling` will be NULL.
 *
 * ## Rabbit Holes
 *
 * On first thought, it might be useful to *always* set `newParent`, if `newSucceedingSibling` is set. After
 * all, having more information inside an event payload might be useful? We decided *against* this,
 * for the following reasons:
 * - we want to remove any ambiguity on how to interpret the event inside different projections. It would
 *   be not good if one projection fell back to `newParent` if `newSucceedingSibling` would not be found; and
 *   another one would throw an exception. We prevent this by only adding one or the other information bit.
 * - We want to reduce the number of allowed combinations, as this makes projection implementer's life easier.
 *
 * Relying on newSucceedingSibling means that in order for a projection to correctly handle this event, it
 * needs to be aware of Node's hierarchy. However, it does NOT need to be aware of the sibling ordering,
 * hierarchy is enough (if it fits your use-case). In this case, simply fetch the parent of `newSucceedingSibling`
 * based on the hierarchy data stored inside your projection.
 * => a projection always needs hierarchy data (but that's also the case for quite some other events).
 *
 * Additionally, we could allow setting *neither* `newParent` nor `newSucceedingSibling` (move to end of current
 * sibling-list). We do NOT support this, again in order to reduce the number of allowed combinations, as this makes
 * projection implementer's life easier.
 *
 * @api events are the persistence-API of the content repository
 */
final class NodeAggregateWasMoved implements
    EventInterface,
    PublishableToOtherContentStreamsInterface,
    EmbedsContentStreamAndNodeAggregateId
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly OriginNodeMoveMappings $nodeMoveMappings,
    ) {
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }

    public function getNodeAggregateId(): NodeAggregateId
    {
        return $this->nodeAggregateId;
    }

    public function createCopyForContentStream(ContentStreamId $targetContentStreamId): self
    {
        return new self(
            $targetContentStreamId,
            $this->nodeAggregateId,
            $this->nodeMoveMappings,
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['nodeAggregateId']),
            OriginNodeMoveMappings::fromArray($values['nodeMoveMappings']),
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
