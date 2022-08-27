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

namespace Neos\ContentRepository\Projection\Workspace;

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;

/**
 * Workspace Read Model
 *
 * @api
 */
class Workspace
{
    /**
     * @internal
     */
    public function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly ?WorkspaceName $baseWorkspaceName,
        public readonly ?WorkspaceTitle $workspaceTitle,
        public readonly WorkspaceDescription $workspaceDescription,
        public readonly ContentStreamIdentifier $currentContentStreamIdentifier,
        public readonly WorkspaceStatus $status,
        public readonly ?string $workspaceOwner
    ) {
    }

    /**
     * Checks if this workspace is shared across all editors
     *
     * @return boolean
     */
    public function isInternalWorkspace()
    {
        return $this->baseWorkspaceName !== null && $this->workspaceOwner === null;
    }

    /**
     * Checks if this workspace is public to everyone, even without authentication
     *
     * @return boolean
     */
    public function isPublicWorkspace()
    {
        return $this->baseWorkspaceName === null && $this->workspaceOwner === null;
    }

    /**
     * Checks if this workspace is a user's personal workspace
     * @api
     */
    public function isPersonalWorkspace(): bool
    {
        return $this->workspaceOwner !== null;
    }
}
