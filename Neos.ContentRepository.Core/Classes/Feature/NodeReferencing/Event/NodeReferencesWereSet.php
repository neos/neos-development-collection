<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeReferencing\Event;

use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\EventStore\EventInterface;

/**
 * Named references with optional properties were created from source node to destination node(s)
 *
 * Replaces all references of the given $referenceName
 *
 * @api events are the persistence-API of the content repository
 */
final class NodeReferencesWereSet implements
    EventInterface,
    PublishableToOtherContentStreamsInterface,
    EmbedsContentStreamAndNodeAggregateId
{
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $sourceNodeAggregateId,
        /**
         * While only one origin dimension space point is selected when initializing the command,
         * a whole set of origin dimension space points might be affected depending on the
         * {@see \Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyScope} scope
         * declared for the given reference in the node aggregate's type
         */
        public readonly OriginDimensionSpacePointSet $affectedSourceOriginDimensionSpacePoints,
        public readonly ReferenceName $referenceName,
        public readonly SerializedNodeReferences $references,
        public readonly UserId $initiatingUserId
    ) {
    }

    public function createCopyForContentStream(ContentStreamId $targetContentStreamId): self
    {
        return new self(
            $targetContentStreamId,
            $this->sourceNodeAggregateId,
            $this->affectedSourceOriginDimensionSpacePoints,
            $this->referenceName,
            $this->references,
            $this->initiatingUserId
        );
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

    public static function fromArray(array $values): self
    {
        return new self(
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['sourceNodeAggregateId']),
            OriginDimensionSpacePointSet::fromArray($values['affectedSourceOriginDimensionSpacePoints']),
            ReferenceName::fromString($values['referenceName']),
            SerializedNodeReferences::fromArray($values['references']),
            UserId::fromString($values['initiatingUserId'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamId' => $this->contentStreamId,
            'sourceNodeAggregateId' => $this->sourceNodeAggregateId,
            'affectedSourceOriginDimensionSpacePoints' => $this->affectedSourceOriginDimensionSpacePoints,
            'referenceName' => $this->referenceName,
            'references' => $this->references,
            'initiatingUserId' => $this->initiatingUserId
        ];
    }
}
