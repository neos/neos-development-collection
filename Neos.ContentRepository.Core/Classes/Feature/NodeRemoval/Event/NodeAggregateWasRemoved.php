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

namespace Neos\ContentRepository\Core\Feature\NodeRemoval\Event;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamId;
use Neos\ContentRepository\Core\Feature\Common\EmbedsNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\EmbedsWorkspaceName;
use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @api events are the persistence-API of the content repository
 */
final readonly class NodeAggregateWasRemoved implements
    EventInterface,
    PublishableToWorkspaceInterface,
    EmbedsContentStreamId,
    EmbedsNodeAggregateId,
    EmbedsWorkspaceName
{
    /**
     * @deprecated with Neos 9 Beta 19. Must not be specified any longer. Might get removed at any point.
     */
    public ?NodeAggregateId $removalAttachmentPoint;

    public function __construct(
        public WorkspaceName $workspaceName,
        public ContentStreamId $contentStreamId,
        public NodeAggregateId $nodeAggregateId,
        public DimensionSpacePointSet $affectedCoveredDimensionSpacePoints,
        ?NodeAggregateId $removalAttachmentPoint = null
    ) {
        $this->removalAttachmentPoint = $removalAttachmentPoint;
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }

    public function getNodeAggregateId(): NodeAggregateId
    {
        return $this->nodeAggregateId;
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public function withWorkspaceNameAndContentStreamId(WorkspaceName $targetWorkspaceName, ContentStreamId $contentStreamId): self
    {
        return new self(
            $targetWorkspaceName,
            $contentStreamId,
            $this->nodeAggregateId,
            $this->affectedCoveredDimensionSpacePoints,
            $this->removalAttachmentPoint
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['nodeAggregateId']),
            DimensionSpacePointSet::fromArray($values['affectedCoveredDimensionSpacePoints']),
            isset($values['removalAttachmentPoint'])
                ? NodeAggregateId::fromString($values['removalAttachmentPoint'])
                : null,
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
