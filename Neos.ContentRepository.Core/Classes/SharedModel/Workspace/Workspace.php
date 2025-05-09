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
 * @api Note: The constructor is not part of the public API
 */
final readonly class Workspace
{
    /**
     * @param WorkspaceName $workspaceName Workspace identifier, unique within one Content Repository instance
     * @param WorkspaceName|null $baseWorkspaceName Workspace identifier of the base workspace (i.e. the target when publishing changes) – if null this instance is considered a root (aka public) workspace
     * @param ContentStreamId $currentContentStreamId The Content Stream this workspace currently points to – usually it is set to a new, empty content stream after publishing/rebasing the workspace
     * @param WorkspaceStatus $status The current status of this workspace
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public ?WorkspaceName $baseWorkspaceName,
        public ContentStreamId $currentContentStreamId,
        public WorkspaceStatus $status,
        private bool $hasPublishableChanges
    ) {
        if ($this->isRootWorkspace() && $this->hasPublishableChanges) {
            throw new \InvalidArgumentException('Root workspaces cannot have changes', 1730371566);
        }
    }

    /**
     * @internal
     */
    public static function create(
        WorkspaceName $workspaceName,
        ?WorkspaceName $baseWorkspaceName,
        ContentStreamId $currentContentStreamId,
        WorkspaceStatus $status,
        bool $hasPublishableChanges
    ): self {
        return new self($workspaceName, $baseWorkspaceName, $currentContentStreamId, $status, $hasPublishableChanges);
    }

    /**
     * Indicates if the workspace contains changed to be published
     */
    public function hasPublishableChanges(): bool
    {
        return $this->hasPublishableChanges;
    }

    /**
     * @phpstan-assert-if-false WorkspaceName $this->baseWorkspaceName
     */
    public function isRootWorkspace(): bool
    {
        return $this->baseWorkspaceName === null;
    }
}
