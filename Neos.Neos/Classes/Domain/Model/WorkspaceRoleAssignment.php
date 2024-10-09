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
        public WorkspaceRoleSubjectType $subjectType,
        public WorkspaceRoleSubject $subject,
        public WorkspaceRole $role,
    ) {
    }

    public static function create(
        WorkspaceRoleSubjectType $subjectType,
        WorkspaceRoleSubject $subject,
        WorkspaceRole $role,
    ): self {
        return new self($subjectType, $subject, $role);
    }

    public static function createForUser(UserId $userId, WorkspaceRole $role): self
    {
        return new self(
            WorkspaceRoleSubjectType::USER,
            WorkspaceRoleSubject::fromString($userId->value),
            $role
        );
    }

    public static function createForGroup(string $flowRoleIdentifier, WorkspaceRole $role): self
    {
        return new self(
            WorkspaceRoleSubjectType::GROUP,
            WorkspaceRoleSubject::fromString($flowRoleIdentifier),
            $role
        );
    }
}
