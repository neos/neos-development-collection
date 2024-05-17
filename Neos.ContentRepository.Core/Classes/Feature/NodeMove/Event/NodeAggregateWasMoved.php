<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeMove\Event;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSibling;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\Common\PublishableInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * A node aggregate was moved in a content stream
 *
 * We always move a node aggregate identified by a NodeAggregateId (in a given ContentStreamId); or
 * parts of the NodeAggregate.
 *
 * The inner structure of the event is rather complicated, that's why the following picture shows it:
 *
 * ```
 *┌───────────────────────────────────┐
 *│   NodeAggregateWasMoved           │
 *│-> contains NodeAggregateId        │
 *│-> contains parent NodeAggregateId │
 *└─────────┬─────────────────────────┘
 *          │
 *          │   ┌────────────────────────────────────────────────────┐
 *          │   │             InterdimensionalSibling                │
 *          │   │     -> contains DimensionSpacePoint                │
 *          │  *│     -> contains succeeding sibling NodeAggregateId │
 *          └───▶                                                    │
 *              │ (1 per affected dimension space point)             │
 *              └────────────────────────────────────────────────────┘
 * ```
 *
 * - We move some parts of a single NodeAggregate (`NodeAggregateWasMoved`).
 * - If given, a single parent NodeAggregateId is provided and to be used for all affected DimensionSpacePoints.
 *   Else, no new parent will be set for any on the variants.
 * - For each affected DimensionSpacePoint, an optional succeeding sibling is provided.
 * -- If a single node is to be moved to the end, the succeeding sibling NodeAggregateId is null
 * -- If a single node is to be moved to the start, the previous first sibling is to be set as succeeding sibling
 * -- If a single node is not to be moved at all, e.g. if no siblings can be determined, it is considered unaffected
 *    and it (its DSP respectively) is not part of the InterdimensionalSibling collection
 *
 * @api events are the persistence-API of the content repository
 */
final readonly class NodeAggregateWasMoved implements
    EventInterface,
    PublishableInterface,
    EmbedsContentStreamAndNodeAggregateId
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public ContentStreamId $contentStreamId,
        public NodeAggregateId $nodeAggregateId,
        public ?NodeAggregateId $newParentNodeAggregateId,
        public InterdimensionalSiblings $succeedingSiblingsForCoverage,
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

    public function createCopyForContentStream(WorkspaceName $targetWorkspaceName, ContentStreamId $targetContentStreamId): self
    {
        return new self(
            $targetWorkspaceName,
            $targetContentStreamId,
            $this->nodeAggregateId,
            $this->newParentNodeAggregateId,
            $this->succeedingSiblingsForCoverage,
        );
    }

    public static function fromArray(array $values): self
    {
        if (array_key_exists('nodeMoveMappings', $values)) {
            $newParentNodeAggregateId = null;
            $succeedingSiblings = [];
            foreach ($values['nodeMoveMappings'] as $nodeMoveMapping) {
                // we don't care about origins anymore
                foreach ($nodeMoveMapping['newLocations'] as $newLocation) {
                    if (array_key_exists('newParent', $newLocation)) {
                        $newParentNodeAggregateId = NodeAggregateId::fromString($newLocation['newParent']);
                    }
                    $succeedingSiblings[] = new InterdimensionalSibling(
                        DimensionSpacePoint::fromArray($newLocation['coveredDimensionSpacePoint']),
                        ($newLocation['newSucceedingSibling'] ?? null)
                            ? NodeAggregateId::fromString($newLocation['newSucceedingSibling'])
                            : null
                    );
                }
            }
            $succeedingSiblingsForCoverage = new InterdimensionalSiblings(...$succeedingSiblings);
        } else {
            $newParentNodeAggregateId = $values['newParentNodeAggregateId'] === null
                ? null
                : NodeAggregateId::fromString($values['newParentNodeAggregateId']);
            $succeedingSiblingsForCoverage = InterdimensionalSiblings::fromArray($values['succeedingSiblingsForCoverage']);
        }

        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['nodeAggregateId']),
            $newParentNodeAggregateId,
            $succeedingSiblingsForCoverage,
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
