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

namespace Neos\ContentRepository\Core\Feature\WorkspacePublication\Event;

use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdsToPublishOrDiscard;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\EventStore\EventInterface;

/**
 * @api events are the persistence-API of the content repository
 */
final class WorkspaceWasPartiallyPublished implements EventInterface
{
    public function __construct(
        /**
         * From which workspace have changes been partially published?
         */
        public readonly WorkspaceName $sourceWorkspaceName,
        /**
         * The target workspace where the changes have been published to.
         */
        public readonly WorkspaceName $targetWorkspaceName,
        /**
         * The new content stream for the $sourceWorkspaceName
         */
        public readonly ContentStreamId $newSourceContentStreamId,
        /**
         * The old content stream, which contains ALL the data (discarded and non-discarded)
         */
        public readonly ContentStreamId $previousSourceContentStreamId,
        public readonly NodeIdsToPublishOrDiscard $publishedNodes,
        public readonly UserId $initiatingUserId
    ) {
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['sourceWorkspaceName']),
            WorkspaceName::fromString($values['targetWorkspaceName']),
            ContentStreamId::fromString($values['newSourceContentStreamId']),
            ContentStreamId::fromString($values['previousSourceContentStreamId']),
            NodeIdsToPublishOrDiscard::fromArray($values['publishedNodes']),
            UserId::fromString($values['initiatingUserId'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'sourceWorkspaceName' => $this->sourceWorkspaceName,
            'targetWorkspaceName' => $this->targetWorkspaceName,
            'newSourceContentStreamId' => $this->newSourceContentStreamId,
            'previousSourceContentStreamId' => $this->previousSourceContentStreamId,
            'publishedNodes' => $this->publishedNodes,
            'initiatingUserId' => $this->initiatingUserId,
        ];
    }
}
