<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

/**
 * Role, a user or user group can have in one workspace
 * Note: "Owner" is not a role, owners implicitly always have all permissions
 *
 * @api
 */
enum WorkspaceRole : string
{
    /**
     * Can read from the workspace
     */
    case VIEWER = 'VIEWER';

    /**
     * Can read from and write to the workspace
     */
    case COLLABORATOR = 'COLLABORATOR';

    /**
     * Can read from and write to the workspace and manage it (i.e. change metadata & role assignments)
     */
    case MANAGER = 'MANAGER';

    public function isAtLeast(self $role): bool
    {
        return $this->specificity() >= $role->specificity();
    }

    private function specificity(): int
    {
        return match ($this) {
            self::VIEWER => 1,
            self::COLLABORATOR => 2,
            self::MANAGER => 3,
        };
    }
}
