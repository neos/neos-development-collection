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
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Create a new workspace, based on an existing baseWorkspace
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class CreateWorkspace implements CommandInterface
{
    /**
     * @param WorkspaceName $workspaceName Unique name of the workspace to create
     * @param WorkspaceName $baseWorkspaceName Name of the base workspace
     * @param ContentStreamId $newContentStreamId The id of the content stream the new workspace is assigned to initially
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public WorkspaceName $baseWorkspaceName,
        public ContentStreamId $newContentStreamId,
    ) {
    }

    /**
     * @param WorkspaceName $workspaceName Unique name of the workspace to create
     * @param WorkspaceName $baseWorkspaceName Name of the base workspace
     * @param ContentStreamId $newContentStreamId The id of the content stream the new workspace is assigned to initially
     */
    public static function create(WorkspaceName $workspaceName, WorkspaceName $baseWorkspaceName, ContentStreamId $newContentStreamId): self
    {
        return new self($workspaceName, $baseWorkspaceName, $newContentStreamId);
    }
}
