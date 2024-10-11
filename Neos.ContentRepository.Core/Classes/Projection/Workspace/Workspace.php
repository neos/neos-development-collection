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
final readonly class Workspace
{
    /**
     * This prefix determines if a given workspace (name) is a user workspace.
     * @deprecated with 9.0.0-beta14 metadata should be assigned to workspaces outside the Content Repository core
     */
    public const PERSONAL_WORKSPACE_PREFIX = 'user-';

    /**
     * @var WorkspaceName Workspace identifier, unique within one Content Repository instance
     */
    public WorkspaceName $workspaceName;

    /**
     * @var WorkspaceName|null Workspace identifier of the base workspace (i.e. the target when publishing changes) – if null this instance is considered a root (aka public) workspace
     */
    public ?WorkspaceName $baseWorkspaceName;

    /**
     * @deprecated with 9.0.0-beta14 metadata should be assigned to workspaces outside the Content Repository core
     */
    public WorkspaceTitle $workspaceTitle;

    /**
     * @deprecated with 9.0.0-beta14 metadata should be assigned to workspaces outside the Content Repository core
     */
    public WorkspaceDescription $workspaceDescription;

    /**
     * The Content Stream this workspace currently points to – usually it is set to a new, empty content stream after publishing/rebasing the workspace
     */
    public ContentStreamId $currentContentStreamId;

    /**
     * The current status of this workspace
     */
    public WorkspaceStatus $status;

    /**
     * @deprecated with 9.0.0-beta14 owners/collaborators should be assigned to workspaces outside the Content Repository core
     */
    public string|null $workspaceOwner;

    /**
     * @internal
     */
    public function __construct(
        WorkspaceName $workspaceName,
        ?WorkspaceName $baseWorkspaceName,
        WorkspaceTitle $workspaceTitle,
        WorkspaceDescription $workspaceDescription,
        ContentStreamId $currentContentStreamId,
        WorkspaceStatus $status,
        ?string $workspaceOwner
    ) {
        $this->workspaceName = $workspaceName;
        $this->baseWorkspaceName = $baseWorkspaceName;
        $this->workspaceTitle = $workspaceTitle;
        $this->workspaceDescription = $workspaceDescription;
        $this->currentContentStreamId = $currentContentStreamId;
        $this->status = $status;
        $this->workspaceOwner = $workspaceOwner;
    }

    /**
     * Checks if this workspace is a user's personal workspace
     * @deprecated with 9.0.0-beta14 owners/collaborators should be assigned to workspaces outside the Content Repository core
     */
    public function isPersonalWorkspace(): bool
    {
        return str_starts_with($this->workspaceName->value, static::PERSONAL_WORKSPACE_PREFIX);
    }

    /**
     * Checks if this workspace is shared only across users with access to internal workspaces, for example "reviewers"
     *
     * @return bool
     * @deprecated with 9.0.0-beta14 owners/collaborators should be assigned to workspaces outside the Content Repository core
     */
    public function isPrivateWorkspace(): bool
    {
        return $this->workspaceOwner !== null && !$this->isPersonalWorkspace();
    }

    /**
     * Checks if this workspace is shared across all editors
     *
     * @return boolean
     * @deprecated with 9.0.0-beta14 owners/collaborators should be assigned to workspaces outside the Content Repository core
     */
    public function isInternalWorkspace(): bool
    {
        return $this->baseWorkspaceName !== null && $this->workspaceOwner === null;
    }

    /**
     * Checks if this workspace is public to everyone, even without authentication
     *
     * @return boolean
     * @deprecated with 9.0.0-beta14 owners/collaborators should be assigned to workspaces outside the Content Repository core
     */
    public function isPublicWorkspace(): bool
    {
        return $this->baseWorkspaceName === null && $this->workspaceOwner === null;
    }

    public function isRootWorkspace(): bool
    {
        return $this->baseWorkspaceName !== null;
    }
}
