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

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;

/**
 * Event triggered to indicate that a workspace was created, based on a base workspace.
 *
 * NOTE: you can rely on the fact that an extra {@see ContentStreamWasForked} event was persisted BEFORE
 * this event for the actual content stream forking.
 *
 * @api events are the persistence-API of the content repository
 */
final readonly class WorkspaceWasCreated implements EventInterface
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public WorkspaceName $baseWorkspaceName,
        public WorkspaceTitle $workspaceTitle,
        public WorkspaceDescription $workspaceDescription,
        public ContentStreamId $newContentStreamId,
        public ?UserId $workspaceOwner = null
    ) {
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            WorkspaceName::fromString($values['baseWorkspaceName']),
            WorkspaceTitle::fromString($values['workspaceTitle']),
            WorkspaceDescription::fromString($values['workspaceDescription']),
            ContentStreamId::fromString($values['newContentStreamId']),
            $values['workspaceOwner'] ? UserId::fromString($values['workspaceOwner']) : null
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
