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

namespace Neos\ContentRepository\Feature\WorkspaceCreation\Command;

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;
use Neos\Flow\Annotations as Flow;

/**
 * Create a new workspace, based on an existing baseWorkspace
 *
 * @Flow\Proxy(false)
 */
final class CreateWorkspace
{
    private WorkspaceName $workspaceName;

    private WorkspaceName $baseWorkspaceName;

    private WorkspaceTitle $workspaceTitle;

    private WorkspaceDescription $workspaceDescription;

    private UserIdentifier $initiatingUserIdentifier;

    /**
     * the content stream identifier for the content stream which is created together with the to-be-created workspace
     */
    private ContentStreamIdentifier $newContentStreamIdentifier;

    private ?UserIdentifier $workspaceOwner;

    public function __construct(
        WorkspaceName $workspaceName,
        WorkspaceName $baseWorkspaceName,
        WorkspaceTitle $workspaceTitle,
        WorkspaceDescription $workspaceDescription,
        UserIdentifier $initiatingUserIdentifier,
        ContentStreamIdentifier $newContentStreamIdentifier = null,
        UserIdentifier $workspaceOwner = null
    ) {
        $this->workspaceName = $workspaceName;
        $this->baseWorkspaceName = $baseWorkspaceName;
        $this->workspaceTitle = $workspaceTitle;
        $this->workspaceDescription = $workspaceDescription;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
        $this->newContentStreamIdentifier = $newContentStreamIdentifier ?: ContentStreamIdentifier::create();
        $this->workspaceOwner = $workspaceOwner;
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public function getBaseWorkspaceName(): WorkspaceName
    {
        return $this->baseWorkspaceName;
    }

    public function getWorkspaceTitle(): WorkspaceTitle
    {
        return $this->workspaceTitle;
    }

    public function getWorkspaceDescription(): WorkspaceDescription
    {
        return $this->workspaceDescription;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    public function getNewContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->newContentStreamIdentifier;
    }

    public function getWorkspaceOwner(): ?UserIdentifier
    {
        return $this->workspaceOwner;
    }
}
