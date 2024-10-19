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
use Neos\ContentRepository\Core\Feature\Common\EmbedsWorkspaceName;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Event triggered to indicate that a workspace was created, based on a base workspace.
 *
 * NOTE: you can rely on the fact that an extra {@see ContentStreamWasForked} event was persisted BEFORE
 * this event for the actual content stream forking.
 *
 * @api events are the persistence-API of the content repository
 */
final readonly class WorkspaceWasCreated implements EventInterface, EmbedsWorkspaceName
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public WorkspaceName $baseWorkspaceName,
        public ContentStreamId $newContentStreamId,
    ) {
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            WorkspaceName::fromString($values['baseWorkspaceName']),
            ContentStreamId::fromString($values['newContentStreamId']),
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
