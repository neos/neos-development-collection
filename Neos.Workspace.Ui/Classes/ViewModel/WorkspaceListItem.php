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
use Neos\Neos\Domain\Model\WorkspaceClassification;
use Neos\Neos\Domain\Model\WorkspacePermissions;
use Neos\Neos\Domain\Model\WorkspaceRole;
use Neos\Neos\Domain\Model\WorkspaceRoleAssignments;

/**
 * @internal for communication within the Workspace UI only
 */
#[Flow\Proxy(false)]
final readonly class WorkspaceListItem
{
    public function __construct(
        public string $name,
        public string $classification,
        public string $status,
        public string $title,
        public string $description,
        public ?string $baseWorkspaceName,
        public PendingChanges $pendingChanges,
        public bool $hasDependantWorkspaces,
        public ?string $owner,
        public WorkspacePermissions $permissions,
        public WorkspaceRoleAssignments $roleAssignments,
    ) {
    }

    public function isPersonal(): bool
    {
        return $this->classification === WorkspaceClassification::PERSONAL->value;
    }

    public function isPrivate(): bool
    {
        if ($this->classification !== WorkspaceClassification::SHARED->value) {
            return false;
        }
        foreach ($this->roleAssignments as $roleAssignment) {
            if ($roleAssignment->role === WorkspaceRole::COLLABORATOR) {
                return false;
            }
        }
        return true;
    }

    public function isShared(): bool
    {
        foreach ($this->roleAssignments as $roleAssignment) {
            if ($roleAssignment->role === WorkspaceRole::COLLABORATOR) {
                return true;
            }
        }
        return false;
    }
}
