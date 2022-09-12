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

namespace Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event;

use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;
use Neos\ContentRepository\Core\EventStore\EventInterface;

/**
 * Event triggered to indicate that a workspace was created, based on a base workspace.
 *
 * NOTE: you can rely on the fact that an extra {@see ContentStreamWasForked} event was persisted BEFORE
 * this event for the actual content stream forking.
 *
 * @api events are the persistence-API of the content repository
 */
final class WorkspaceWasCreated implements EventInterface
{
    public function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly WorkspaceName $baseWorkspaceName,
        public readonly WorkspaceTitle $workspaceTitle,
        public readonly WorkspaceDescription $workspaceDescription,
        public readonly UserId $initiatingUserId,
        public readonly ContentStreamId $newContentStreamId,
        public readonly ?UserId $workspaceOwner = null
    ) {
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            WorkspaceName::fromString($values['baseWorkspaceName']),
            WorkspaceTitle::fromString($values['workspaceTitle']),
            WorkspaceDescription::fromString($values['workspaceDescription']),
            UserId::fromString($values['initiatingUserId']),
            ContentStreamId::fromString($values['newContentStreamId']),
            $values['workspaceOwner'] ? UserId::fromString($values['workspaceOwner']) : null
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'workspaceName' => $this->workspaceName,
            'baseWorkspaceName' => $this->baseWorkspaceName,
            'workspaceTitle' => $this->workspaceTitle,
            'workspaceDescription' => $this->workspaceDescription,
            'initiatingUserId' => $this->initiatingUserId,
            'newContentStreamId' => $this->newContentStreamId,
            'workspaceOwner' => $this->workspaceOwner
        ];
    }
}
