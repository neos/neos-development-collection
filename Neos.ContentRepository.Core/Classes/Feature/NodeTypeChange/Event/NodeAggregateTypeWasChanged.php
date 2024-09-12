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

namespace Neos\ContentRepository\Core\Feature\NodeTypeChange\Event;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamAndNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @api events are the persistence-API of the content repository
 */
final readonly class NodeAggregateTypeWasChanged implements
    EventInterface,
    PublishableToWorkspaceInterface,
    EmbedsContentStreamAndNodeAggregateId
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public ContentStreamId $contentStreamId,
        public NodeAggregateId $nodeAggregateId,
        public NodeTypeName $newNodeTypeName
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

    public function withWorkspaceNameAndContentStreamId(WorkspaceName $targetWorkspaceName, ContentStreamId $contentStreamId): self
    {
        return new self(
            $targetWorkspaceName,
            $contentStreamId,
            $this->nodeAggregateId,
            $this->newNodeTypeName
        );
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['nodeAggregateId']),
            NodeTypeName::fromString($values['newNodeTypeName']),
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
