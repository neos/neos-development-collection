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

namespace Neos\ContentRepository\Core\SharedModel\Workspace;

/**
 * Workspace Read Model
 *
 * @api
 */
final readonly class Workspace
{
    /**
     * @var WorkspaceName Workspace identifier, unique within one Content Repository instance
     */
    public WorkspaceName $workspaceName;

    /**
     * @var WorkspaceName|null Workspace identifier of the base workspace (i.e. the target when publishing changes) – if null this instance is considered a root (aka public) workspace
     */
    public ?WorkspaceName $baseWorkspaceName;

    /**
     * The Content Stream this workspace currently points to – usually it is set to a new, empty content stream after publishing/rebasing the workspace
     */
    public ContentStreamId $currentContentStreamId;

    /**
     * The current status of this workspace
     */
    public WorkspaceStatus $status;

    /**
     * @internal
     */
    public function __construct(
        WorkspaceName $workspaceName,
        ?WorkspaceName $baseWorkspaceName,
        ContentStreamId $currentContentStreamId,
        WorkspaceStatus $status,
    ) {
        $this->workspaceName = $workspaceName;
        $this->baseWorkspaceName = $baseWorkspaceName;
        $this->currentContentStreamId = $currentContentStreamId;
        $this->status = $status;
    }

    public function isRootWorkspace(): bool
    {
        return $this->baseWorkspaceName !== null;
    }
}
