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
    public function __construct(
        public WorkspaceRoleSubjectType $subjectType,
        public WorkspaceRoleSubject $subject,
        public WorkspaceRole $role,
    ) {
    }
}
