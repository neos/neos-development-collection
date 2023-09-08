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

namespace Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;

/**
 * Create a new workspace, based on an existing baseWorkspace
 *
 * @api commands are the write-API of the ContentRepository
 */
final class CreateWorkspace implements CommandInterface
{
    /**
     * @param WorkspaceName $workspaceName Unique name of the workspace to create
     * @param WorkspaceName $baseWorkspaceName Name of the base workspace
     * @param WorkspaceTitle $workspaceTitle Human-readable title of the workspace to create (can be changed)
     * @param WorkspaceDescription $workspaceDescription Description of the workspace to create (can be changed)
     * @param ContentStreamId $newContentStreamId The id of the content stream the new workspace is assigned to initially
     * @param UserId|null $workspaceOwner Owner of the new workspace (optional)
     */
    private function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly WorkspaceName $baseWorkspaceName,
        public readonly WorkspaceTitle $workspaceTitle,
        public readonly WorkspaceDescription $workspaceDescription,
        public readonly ContentStreamId $newContentStreamId,
        public readonly ?UserId $workspaceOwner,
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName Unique name of the workspace to create
     * @param WorkspaceName $baseWorkspaceName Name of the base workspace
     * @param WorkspaceTitle $workspaceTitle Human-readable title of the workspace to create (can be changed)
     * @param WorkspaceDescription $workspaceDescription Description of the workspace to create (can be changed)
     * @param ContentStreamId $newContentStreamId The id of the content stream the new workspace is assigned to initially
     * @param UserId|null $workspaceOwner Owner of the new workspace (optional)
     */
    public static function create(WorkspaceName $workspaceName, WorkspaceName $baseWorkspaceName, WorkspaceTitle $workspaceTitle, WorkspaceDescription $workspaceDescription, ContentStreamId $newContentStreamId, ?UserId $workspaceOwner = null): self
    {
        return new self($workspaceName, $baseWorkspaceName, $workspaceTitle, $workspaceDescription, $newContentStreamId, $workspaceOwner);
    }
}
