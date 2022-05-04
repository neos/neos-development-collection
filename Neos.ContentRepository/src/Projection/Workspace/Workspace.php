<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Projection\Workspace;

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
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;

/**
 * Workspace Read Model
 */
class Workspace
{

    /**
     * @var string
     */
    public $workspaceName;

    /**
     * @var string
     */
    public $baseWorkspaceName;

    /**
     * @var string
     */
    public $workspaceTitle;

    /**
     * @var string
     */
    public $workspaceDescription;

    /**
     * @var string
     */
    public $workspaceOwner;

    /**
     * @var string
     */
    public $currentContentStreamIdentifier;


    /**
     * one of the STATUS_* constants
     *
     * @var string
     */
    public $status;

    /**
     * UP TO DATE Example:
     *
     * Workspace Review <-- Workspace User-Foo
     *     |                    |
     *   Content Stream A <-- Content Stream B
     *
     * This is the case if the contentStream of the base workspace IS EQUAL TO the sourceContentStream
     * of this workspace's content stream.
     *
     * By definition, a base workspace (like "live") is ALWAYS UP_TO_DATE.
     */
    const STATUS_UP_TO_DATE = 'UP_TO_DATE';

    /**
     * A workspace can be OUTDATED because of two reasons:
     *
     * REASON 1: The base content stream has been rebased
     *
     *     Workspace Review <------------ Workspace User-Foo
     *      .   |                                 |
     *      .   Content Stream A2 (current)       |
     *      Content Stream A1 (previous) <-- Content Stream B
     *
     *     This is the case if the contentStream of the base workspace IS NEWER THAN the sourceContentStream
     *     of this workspace's content stream.
     *
     *     In the example, Content Stream B would need to be rebased to Content stream A2.
     *
     *
     * REASON 2: The base content stream has new events
     *
     *     In case the base content stream (e.g. "Content Stream A" in the example)
     *     has events applied to it *AFTER* the fork-point (when "Content Stream B" is created), the workspace
     *     will also be marked as "outdated".
     */
    const STATUS_OUTDATED = 'OUTDATED';

    /**
     * CONFLICT Example:
     *
     * CONFLICT is a special case of OUTDATED, but then an error happens during the rebasing.
     *
     * Workspace Review <----------------------------------- Workspace User-Foo
     *      |                                                .             |
     *      Content Stream A2 (current)  <-- Content Stream B2 (rebasing)  |
     *                                                        Content Stream B1
     */
    const STATUS_OUTDATED_CONFLICT = 'OUTDATED_CONFLICT';

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
     * Checks if the workspace is the root workspace
     *
     * @return boolean
     */
    public function isRootWorkspace()
    {
        return $this->baseWorkspaceName === null;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getCurrentContentStreamIdentifier(): ContentStreamIdentifier
    {
        return ContentStreamIdentifier::fromString($this->currentContentStreamIdentifier);
    }

    /**
     * @return WorkspaceName|null
     */
    public function getBaseWorkspaceName(): ?WorkspaceName
    {
        return $this->baseWorkspaceName ? WorkspaceName::fromString($this->baseWorkspaceName) : null;
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return WorkspaceName::fromString($this->workspaceName);
    }

    public function getWorkspaceTitle(): WorkspaceTitle
    {
        return new WorkspaceTitle($this->workspaceTitle);
    }

    /**
     * @return string
     */
    public function getWorkspaceOwner(): ?string
    {
        return $this->workspaceOwner;
    }

    /**
     * Checks if this workspace is a user's personal workspace
     * @api
     */
    public function isPersonalWorkspace(): bool
    {
        return $this->workspaceOwner !== null;
    }

    /**
     * @param array<string,string> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        $workspace = new Workspace();
        $workspace->workspaceName = $row['workspacename'];
        $workspace->baseWorkspaceName = $row['baseworkspacename'];
        $workspace->workspaceTitle = $row['workspacetitle'];
        $workspace->workspaceDescription = $row['workspacedescription'];
        $workspace->workspaceOwner = $row['workspaceowner'];
        $workspace->currentContentStreamIdentifier = $row['currentcontentstreamidentifier'];
        $workspace->status = $row['status'];

        return $workspace;
    }
}
