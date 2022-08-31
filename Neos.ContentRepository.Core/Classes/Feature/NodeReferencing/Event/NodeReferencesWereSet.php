<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeReferencing\Event;

use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateIdentifier;
use Neos\ContentRepository\Core\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\SharedModel\User\UserIdentifier;
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
    EmbedsContentStreamAndNodeAggregateIdentifier
{
    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $sourceNodeAggregateIdentifier,
        /**
         * While only one origin dimension space point is selected when initializing the command,
         * a whole set of origin dimension space points might be affected depending on the
         * {@see \Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyScope} scope
         * declared for the given reference in the node aggregate's type
         */
        public readonly OriginDimensionSpacePointSet $affectedSourceOriginDimensionSpacePoints,
        public readonly ReferenceName $referenceName,
        public readonly SerializedNodeReferences $references,
        public readonly UserIdentifier $initiatingUserIdentifier
    ) {
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStreamIdentifier): self
    {
        return new self(
            $targetContentStreamIdentifier,
            $this->sourceNodeAggregateIdentifier,
            $this->affectedSourceOriginDimensionSpacePoints,
            $this->referenceName,
            $this->references,
            $this->initiatingUserIdentifier
        );
    }

    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * this method is implemented for fulfilling the {@see EmbedsContentStreamAndNodeAggregateIdentifier} interface,
     * needed for proper content cache flushing in Neos.
     *
     * @return NodeAggregateIdentifier
     */
    public function getNodeAggregateIdentifier(): NodeAggregateIdentifier
    {
        return $this->sourceNodeAggregateIdentifier;
    }

    public static function fromArray(array $values): self
    {
        return new self(
            ContentStreamIdentifier::fromString($values['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($values['sourceNodeAggregateIdentifier']),
            OriginDimensionSpacePointSet::fromArray($values['affectedSourceOriginDimensionSpacePoints']),
            ReferenceName::fromString($values['referenceName']),
            SerializedNodeReferences::fromArray($values['references']),
            UserIdentifier::fromString($values['initiatingUserIdentifier'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'contentStreamIdentifier' => $this->contentStreamIdentifier,
            'sourceNodeAggregateIdentifier' => $this->sourceNodeAggregateIdentifier,
            'affectedSourceOriginDimensionSpacePoints' => $this->affectedSourceOriginDimensionSpacePoints,
            'referenceName' => $this->referenceName,
            'references' => $this->references,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier
        ];
    }
}
