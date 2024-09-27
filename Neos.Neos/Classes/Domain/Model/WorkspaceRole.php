<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

/**
 * Role, a user can have in one workspace
 * Note: "Owner" is not a role, owners implicitly always have all permissions
 *
 * @api
 */
enum WorkspaceRole : string
{
    case COLLABORATOR = 'COLLABORATOR';
    case MANAGER = 'MANAGER';

    public function isAtLeast(self $role): bool
    {
        return $this->specifity() >= $role->specifity();
    }

    private function specifity(): int
    {
        return match ($this) {
            self::COLLABORATOR => 1,
            self::MANAGER => 2,
        };
    }
}
