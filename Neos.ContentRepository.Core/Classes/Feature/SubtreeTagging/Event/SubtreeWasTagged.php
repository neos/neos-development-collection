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

namespace Neos\ContentRepository\Core\Feature\SubtreeTagging\Event;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\PublishableInterface;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * A {@see SubtreeTag} was added to a node aggregate and effectively to its descendants
 *
 * @api events are the persistence-API of the content repository
 */
final readonly class SubtreeWasTagged implements
    EventInterface,
    PublishableInterface,
    EmbedsContentStreamAndNodeAggregateId
{
    /**
     * @param ContentStreamId $contentStreamId The content stream id the tag was added in
     * @param NodeAggregateId $nodeAggregateId The id of the node aggregate the tag was explicitly added on
     * @param DimensionSpacePointSet $affectedDimensionSpacePoints The dimension space points the tag was added for
     * @param SubtreeTag $tag The tag that was added
     */
    public function __construct(
        public WorkspaceName $workspaceName,
        public ContentStreamId $contentStreamId,
        public NodeAggregateId $nodeAggregateId,
        public DimensionSpacePointSet $affectedDimensionSpacePoints,
        public SubtreeTag $tag,
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
            $this->affectedDimensionSpacePoints,
            $this->tag,
        );
    }

    public static function fromArray(array $values): EventInterface
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
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
