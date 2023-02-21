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

namespace Neos\ContentRepository\Core\Projection\Workspace;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;

/**
 * Workspace Read Model
 *
 * @api
 */
class Workspace
{

    /**
     * This prefix determines if a given workspace (name) is a user workspace.
     */
    public const PERSONAL_WORKSPACE_PREFIX = 'user-';

    /**
     * @internal
     */
    public function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly ?WorkspaceName $baseWorkspaceName,
        public readonly ?WorkspaceTitle $workspaceTitle,
        public readonly WorkspaceDescription $workspaceDescription,
        public readonly ContentStreamId $currentContentStreamId,
        public readonly WorkspaceStatus $status,
        public readonly ?string $workspaceOwner
    ) {
    }


    /**
     * Checks if this workspace is a user's personal workspace
     * @api
     */
    public function isPersonalWorkspace(): bool
    {
        return str_starts_with($this->workspaceName->name, static::PERSONAL_WORKSPACE_PREFIX);
    }

    /**
     * Checks if this workspace is shared only across users with access to internal workspaces, for example "reviewers"
     *
     * @return bool
     * @api
     */
    public function isPrivateWorkspace(): bool
    {
        return $this->workspaceOwner !== null && !$this->isPersonalWorkspace();
    }

    /**
     * Checks if this workspace is shared across all editors
     *
     * @return boolean
     */
    public function isInternalWorkspace(): bool
    {
        return $this->baseWorkspaceName !== null && $this->workspaceOwner === null;
    }

    /**
     * Checks if this workspace is public to everyone, even without authentication
     *
     * @return boolean
     */
    public function isPublicWorkspace(): bool
    {
        return $this->baseWorkspaceName === null && $this->workspaceOwner === null;
    }

}
