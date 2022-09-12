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

namespace Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\EventStore\EventInterface;

/**
 * @api events are the persistence-API of the content repository
 */
final class WorkspaceWasRebased implements EventInterface
{
    public function __construct(
        public readonly WorkspaceName $workspaceName,
        /**
         * The new content stream ID (after the rebase was successful)
         */
        public readonly ContentStreamId $newContentStreamId,
        /**
         * The old content stream ID (which is not active anymore now)
         */
        public readonly ContentStreamId $previousContentStreamId,
        public readonly UserId $initiatingUserId
    ) {
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            ContentStreamId::fromString($values['newContentStreamId']),
            ContentStreamId::fromString($values['previousContentStreamId']),
            UserId::fromString($values['initiatingUserId']),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'workspaceName' => $this->workspaceName,
            'newContentStreamId' => $this->newContentStreamId,
            'previousContentStreamId' => $this->previousContentStreamId,
            'initiatingUserId' => $this->initiatingUserId,
        ];
    }
}
