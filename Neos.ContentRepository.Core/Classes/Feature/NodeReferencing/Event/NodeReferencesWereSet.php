<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeReferencing\Event;

use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Named references with optional properties were created from source node to destination node(s)
 *
 * Replaces all references of the given $referenceName
 *
 * @api events are the persistence-API of the content repository
 */
final readonly class NodeReferencesWereSet implements
    EventInterface,
    PublishableToWorkspaceInterface,
    EmbedsContentStreamAndNodeAggregateId
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public ContentStreamId $contentStreamId,
        public NodeAggregateId $sourceNodeAggregateId,
        /**
         * While only one origin dimension space point is selected when initializing the command,
         * a whole set of origin dimension space points might be affected depending on the
         * {@see \Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyScope} scope
         * declared for the given reference in the node aggregate's type
         */
        public OriginDimensionSpacePointSet $affectedSourceOriginDimensionSpacePoints,
        public ReferenceName $referenceName,
        public SerializedNodeReferences $references,
    ) {
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }

    /**
     * this method is implemented for fulfilling the {@see EmbedsContentStreamAndNodeAggregateId} interface,
     * needed for proper content cache flushing in Neos.
     *
     * @return NodeAggregateId
     */
    public function getNodeAggregateId(): NodeAggregateId
    {
        return $this->sourceNodeAggregateId;
    }

    public function withWorkspaceNameAndContentStreamId(WorkspaceName $targetWorkspaceName, ContentStreamId $contentStreamId): self
    {
        return new self(
            $targetWorkspaceName,
            $contentStreamId,
            $this->sourceNodeAggregateId,
            $this->affectedSourceOriginDimensionSpacePoints,
            $this->referenceName,
            $this->references,
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['sourceNodeAggregateId']),
            OriginDimensionSpacePointSet::fromArray($values['affectedSourceOriginDimensionSpacePoints']),
            ReferenceName::fromString($values['referenceName']),
            SerializedNodeReferences::fromArray($values['references']),
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
