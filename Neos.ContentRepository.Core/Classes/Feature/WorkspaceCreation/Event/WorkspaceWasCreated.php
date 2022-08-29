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
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Core\SharedModel\User\UserIdentifier;
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
        public readonly UserIdentifier $initiatingUserIdentifier,
        public readonly ContentStreamIdentifier $newContentStreamIdentifier,
        public readonly ?UserIdentifier $workspaceOwner = null
    ) {
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            WorkspaceName::fromString($values['baseWorkspaceName']),
            WorkspaceTitle::fromString($values['workspaceTitle']),
            WorkspaceDescription::fromString($values['workspaceDescription']),
            UserIdentifier::fromString($values['initiatingUserIdentifier']),
            ContentStreamIdentifier::fromString($values['newContentStreamIdentifier']),
            $values['workspaceOwner'] ? UserIdentifier::fromString($values['workspaceOwner']) : null
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'workspaceName' => $this->workspaceName,
            'baseWorkspaceName' => $this->baseWorkspaceName,
            'workspaceTitle' => $this->workspaceTitle,
            'workspaceDescription' => $this->workspaceDescription,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier,
            'newContentStreamIdentifier' => $this->newContentStreamIdentifier,
            'workspaceOwner' => $this->workspaceOwner
        ];
    }
}
