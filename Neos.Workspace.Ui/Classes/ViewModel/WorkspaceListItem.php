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

namespace Neos\Workspace\Ui\ViewModel;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\WorkspacePermissions;

#[Flow\Proxy(false)]
final readonly class WorkspaceListItem
{
    public function __construct(
        public string $name,
        // todo unused!!
        public string $classification,
        public string $status,
        public string $title,
        public string $description,
        public ?string $baseWorkspaceName,
        public PendingChanges $pendingChanges,
        public bool $hasDependantWorkspaces,
        // todo check if necessary, only for personal workspaces that others have permissions to
        public ?string $owner,
        public WorkspacePermissions $permissions,
    ) {
    }
}
