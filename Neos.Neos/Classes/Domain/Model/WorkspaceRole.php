<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

/**
 * Role, a user can have in one workspace
 *
 * @api
 */
enum WorkspaceRole : int
{
    case NONE = 0;
    case COLLABORATOR = 1;
    case MANAGER = 2;
    case OWNER = 3;

    public function isAtLeast(self $role): bool
    {
        return $this->value >= $role->value;
    }
}
