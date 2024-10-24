<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Neos\Flow\Annotations as Flow;

/**
 * Assignment of a workspace role to a Neos user or user group
 *
 * @api
 */
#[Flow\Proxy(false)]
final readonly class WorkspaceRoleAssignment
{
    private function __construct(
        public WorkspaceRoleSubject $subject,
        public WorkspaceRole $role,
    ) {
    }

    public static function create(
        WorkspaceRoleSubject $subject,
        WorkspaceRole $role,
    ): self {
        return new self($subject, $role);
    }

    public static function createForUser(UserId $userId, WorkspaceRole $role): self
    {
        return new self(
            WorkspaceRoleSubject::createForUser($userId),
            $role
        );
    }

    public static function createForGroup(string $flowRoleIdentifier, WorkspaceRole $role): self
    {
        return new self(
            WorkspaceRoleSubject::createForGroup($flowRoleIdentifier),
            $role
        );
    }
}
