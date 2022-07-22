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

namespace Neos\ContentRepository\Feature\WorkspacePublication\Event;

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class WorkspaceWasPublished implements DomainEventInterface
{
    /**
     * From which workspace have changes been published?
     */
    private WorkspaceName $sourceWorkspaceName;

    /**
     * The target workspace where the changes have been published to.
     */
    private WorkspaceName $targetWorkspaceName;

    /**
     * The new, empty content stream identifier of $sourceWorkspaceName, (after the publish was successful)
     */
    private ContentStreamIdentifier $newSourceContentStreamIdentifier;

    /**
     * The old content stream identifier of $sourceWorkspaceName (which is not active anymore now)
     */
    private ContentStreamIdentifier $previousSourceContentStreamIdentifier;

    private UserIdentifier $initiatingUserIdentifier;

    public function __construct(
        WorkspaceName $sourceWorkspaceName,
        WorkspaceName $targetWorkspaceName,
        ContentStreamIdentifier $newSourceContentStreamIdentifier,
        ContentStreamIdentifier $previousSourceContentStreamIdentifier,
        UserIdentifier $initiatingUserIdentifier
    ) {
        $this->sourceWorkspaceName = $sourceWorkspaceName;
        $this->targetWorkspaceName = $targetWorkspaceName;
        $this->newSourceContentStreamIdentifier = $newSourceContentStreamIdentifier;
        $this->previousSourceContentStreamIdentifier = $previousSourceContentStreamIdentifier;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    public function getSourceWorkspaceName(): WorkspaceName
    {
        return $this->sourceWorkspaceName;
    }

    public function getTargetWorkspaceName(): WorkspaceName
    {
        return $this->targetWorkspaceName;
    }

    public function getNewSourceContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->newSourceContentStreamIdentifier;
    }

    public function getPreviousSourceContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->previousSourceContentStreamIdentifier;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }
}
