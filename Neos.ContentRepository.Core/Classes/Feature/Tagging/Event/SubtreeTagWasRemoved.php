<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Tagging\Event;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\Core\Feature\Tagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * A {@see SubtreeTag} was removed from a node aggregate and effectively from its descendants
 * Note: This event means that a tag and all inherited instances were removed. If the same tag was added for another Subtree below this aggregate, this will still be set!
 *
 * @api events are the persistence-API of the content repository
 */
final class SubtreeTagWasRemoved implements
    EventInterface,
    PublishableToOtherContentStreamsInterface,
    EmbedsContentStreamAndNodeAggregateId
{
    /**
     * @param ContentStreamId $contentStreamId The content stream id the tag was removed in
     * @param NodeAggregateId $nodeAggregateId The id of the node aggregate the tag was explicitly removed on
     * @param DimensionSpacePointSet $affectedDimensionSpacePoints The dimension space points the tag was removed for
     * @param SubtreeTag $tag The tag that was removed
     */
    public function __construct(
        public readonly ContentStreamId $contentStreamId,
        public readonly NodeAggregateId $nodeAggregateId,
        public readonly DimensionSpacePointSet $affectedDimensionSpacePoints,
        public readonly SubtreeTag $tag,
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
            $this->affectedDimensionSpacePoints,
            $this->tag,
        );
    }

    public static function fromArray(array $values): EventInterface
    {
        return new self(
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['nodeAggregateId']),
            DimensionSpacePointSet::fromArray($values['affectedDimensionSpacePoints']),
            SubtreeTag::fromString($values['tag']),
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
