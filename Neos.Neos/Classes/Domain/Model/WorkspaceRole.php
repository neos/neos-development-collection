<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

/**
 * Role, a user can have in one workspace
 *
 * @api
 */
enum WorkspaceRole : string
{
    case NONE = 'NONE';
    case COLLABORATOR = 'COLLABORATOR';
    case MANAGER = 'MANAGER';
    case OWNER = 'OWNER';

    public function isAtLeast(self $role): bool
    {
        return $this->specifity() >= $role->specifity();
    }

    private function specifity(): int
    {
        return match ($this) {
            self::NONE => 0,
            self::COLLABORATOR => 1,
            self::MANAGER => 2,
            self::OWNER => 3,
        };
    }
}
