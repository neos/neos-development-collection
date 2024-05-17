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

namespace Neos\ContentRepository\Core\Feature\NodeDisabling\Event;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\PublishableInterface;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * A node aggregate was disabled
 *
 * @deprecated This event will never be emitted, it is up-casted to a corresponding {@see SubtreeWasTagged} event instead in the {@see EventNormalizer}. This implementation is just kept for backwards-compatibility
 * @internal
 */
final readonly class NodeAggregateWasDisabled implements
    EventInterface,
    PublishableInterface,
    EmbedsContentStreamAndNodeAggregateId
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public ContentStreamId $contentStreamId,
        public NodeAggregateId $nodeAggregateId,
        /** The dimension space points the node aggregate was disabled in */
        public DimensionSpacePointSet $affectedDimensionSpacePoints,
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
        );
    }

    public static function fromArray(array $values): EventInterface
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['nodeAggregateId']),
            DimensionSpacePointSet::fromArray($values['affectedDimensionSpacePoints']),
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
