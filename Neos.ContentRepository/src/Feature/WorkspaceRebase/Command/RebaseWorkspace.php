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

namespace Neos\ContentRepository\Feature\WorkspaceRebase\Command;

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * Rebase a workspace
 */
#[Flow\Proxy(false)]
final class RebaseWorkspace
{
    private WorkspaceName $workspaceName;

    private UserIdentifier $initiatingUserIdentifier;

    private ContentStreamIdentifier $rebasedContentStreamIdentifier;

    public function __construct(
        WorkspaceName $workspaceName,
        UserIdentifier $initiatingUserIdentifier,
        ContentStreamIdentifier $rebasedContentStreamIdentifier
    ) {
        $this->workspaceName = $workspaceName;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
        $this->rebasedContentStreamIdentifier = $rebasedContentStreamIdentifier;
    }

    public static function create(WorkspaceName $workspaceName, UserIdentifier $initiatingUserIdentifier): self
    {
        return new self($workspaceName, $initiatingUserIdentifier, ContentStreamIdentifier::create());
    }

    /**
     * Call this method if you want to run this command fully deterministically, f.e. during test cases
     */
    public static function createFullyDeterministic(
        WorkspaceName $workspaceName,
        UserIdentifier $initiatingUserIdentifier,
        ContentStreamIdentifier $newContentStreamIdentifier
    ): self {
        return new self($workspaceName, $initiatingUserIdentifier, $newContentStreamIdentifier);
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    /**
     * Name of the new content stream which is created during the rebase
     */
    public function getRebasedContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->rebasedContentStreamIdentifier;
    }
}
