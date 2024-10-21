<?php

/*
 * This file is part of the Neos.Workspace.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\Model;


use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\Workspace\Ui\ViewModel\PendingChanges;

/**
 * Details of a workspace for the workspace list in the UI
 */
final readonly class WorkspaceDetails
{
    public function __construct(
        public Workspace $workspace,
        public ?string $workspaceOwnerHumanReadable = null, // todo rename
        public ?PendingChanges $changesCounts = null, // todo rename
        public int $dependentWorkspacesCount = 0,
        public bool $canPublish = false,
        public bool $canManage = false,
        public bool $canDelete = false,
    ) {
    }
}
