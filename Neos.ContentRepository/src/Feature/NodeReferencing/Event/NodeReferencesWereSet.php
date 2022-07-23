<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\NodeReferencing\Event;

use Neos\ContentRepository\Feature\Common\SerializedNodeReferences;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\Common\EmbedsContentStreamAndNodeAggregateIdentifier;
use Neos\ContentRepository\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\SharedModel\Node\PropertyName;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Named references with optional properties were created from source node to destination node(s)
 */
#[Flow\Proxy(false)]
final class NodeReferencesWereSet implements
    DomainEventInterface,
    PublishableToOtherContentStreamsInterface,
    EmbedsContentStreamAndNodeAggregateIdentifier
{
    public function __construct(
        public readonly ContentStreamIdentifier $contentStreamIdentifier,
        public readonly NodeAggregateIdentifier $sourceNodeAggregateIdentifier,
        public readonly OriginDimensionSpacePointSet $affectedSourceOriginDimensionSpacePoints,
        public readonly PropertyName $referenceName,
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
}
