<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Feature\WorkspaceDiscarding\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
class WorkspaceWasDiscarded implements DomainEventInterface
{
    private WorkspaceName $workspaceName;

    /**
     * The new, empty, content stream
     */
    private ContentStreamIdentifier $newContentStreamIdentifier;

    /**
     * The old content stream (which contains the discarded data)
     */
    private ContentStreamIdentifier $previousContentStreamIdentifier;

    private UserIdentifier $initiatingUserIdentifier;

    public function __construct(
        WorkspaceName $workspaceName,
        ContentStreamIdentifier $newContentStreamIdentifier,
        ContentStreamIdentifier $previousContentStreamIdentifier,
        UserIdentifier $initiatingUserIdentifier
    ) {
        $this->workspaceName = $workspaceName;
        $this->newContentStreamIdentifier = $newContentStreamIdentifier;
        $this->previousContentStreamIdentifier = $previousContentStreamIdentifier;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public function getNewContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->newContentStreamIdentifier;
    }

    public function getPreviousContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->previousContentStreamIdentifier;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }
}
