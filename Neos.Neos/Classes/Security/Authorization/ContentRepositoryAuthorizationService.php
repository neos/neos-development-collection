<?php

declare(strict_types=1);

namespace Neos\Neos\Security\Authorization;

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Exception\NoSuchRoleException;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Policy\Role;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Model\WorkspacePermissions;
use Neos\Neos\Domain\Model\WorkspaceRole;
use Neos\Neos\Domain\Model\WorkspaceRoleSubject;
use Neos\Neos\Domain\Model\WorkspaceRoleSubjects;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Domain\Service\WorkspaceService;
use Neos\Neos\Security\Authorization\Privilege\SubtreeTagPrivilege;
use Neos\Neos\Security\Authorization\Privilege\SubtreeTagPrivilegeSubject;

/**
 * @api
 */
#[Flow\Scope('singleton')]
final readonly class ContentRepositoryAuthorizationService
{
    private const FLOW_ROLE_EVERYBODY = 'Neos.Flow:Everybody';
    private const FLOW_ROLE_ANONYMOUS = 'Neos.Flow:Anonymous';
    private const FLOW_ROLE_ADMINISTRATOR = 'Neos.Neos:Administrator';


    public function __construct(
        private UserService $userService,
        private WorkspaceService $workspaceService,
        private PolicyService $policyService,
        private PrivilegeManagerInterface $privilegeManager,
    ) {
    }

    public function getWorkspacePermissionsForAnonymousUser(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): WorkspacePermissions
    {
        $subjects = [WorkspaceRoleSubject::createForGroup(self::FLOW_ROLE_EVERYBODY), WorkspaceRoleSubject::createForGroup(self::FLOW_ROLE_ANONYMOUS)];
        $userWorkspaceRole = $this->workspaceService->getMostPrivilegedWorkspaceRoleForSubjects($contentRepositoryId, $workspaceName, WorkspaceRoleSubjects::fromArray($subjects));
        if ($userWorkspaceRole === null) {
            return WorkspacePermissions::none("Anonymous user has no explicit role for workspace '{$workspaceName->value}'");
        }
        return WorkspacePermissions::create(
            read: $userWorkspaceRole->isAtLeast(WorkspaceRole::VIEWER),
            write: $userWorkspaceRole->isAtLeast(WorkspaceRole::COLLABORATOR),
            manage: $userWorkspaceRole->isAtLeast(WorkspaceRole::MANAGER),
            reason: "Anonymous user has role '{$userWorkspaceRole->value}' for workspace '{$workspaceName->value}'",
        );
    }

    /**
     * Determines the permission the given user has for the specified workspace {@see WorkspacePermissions}
     */
    public function getWorkspacePermissionsForUser(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, User $user): WorkspacePermissions
    {
        $workspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepositoryId, $workspaceName);
        if ($workspaceMetadata->ownerUserId !== null && $workspaceMetadata->ownerUserId->equals($user->getId())) {
            return WorkspacePermissions::all("User '{$user->getLabel()}' (id: {$user->getId()->value} is the owner of workspace '{$workspaceName->value}'");
        }
        $userRoles = $this->rolesForUser($user);
        $userIsAdministrator = array_key_exists(self::FLOW_ROLE_ADMINISTRATOR, $userRoles);
        $subjects = array_map(WorkspaceRoleSubject::createForGroup(...), array_keys($userRoles));
        $subjects[] = WorkspaceRoleSubject::createForUser($user->getId());
        $userWorkspaceRole = $this->workspaceService->getMostPrivilegedWorkspaceRoleForSubjects($contentRepositoryId, $workspaceName, WorkspaceRoleSubjects::fromArray($subjects));
        if ($userWorkspaceRole === null) {
            if ($userIsAdministrator) {
                return WorkspacePermissions::manage("User '{$user->getLabel()}' (id: '{$user->getId()->value}') has no explicit role for workspace '{$workspaceName->value}' but is an Administrator");
            }
            return WorkspacePermissions::none("User '{$user->getLabel()}' (id: '{$user->getId()->value}') has no explicit role for workspace '{$workspaceName->value}' and is no Administrator");
        }
        return WorkspacePermissions::create(
            read: $userWorkspaceRole->isAtLeast(WorkspaceRole::VIEWER),
            write: $userWorkspaceRole->isAtLeast(WorkspaceRole::COLLABORATOR),
            manage: $userIsAdministrator || $userWorkspaceRole->isAtLeast(WorkspaceRole::MANAGER),
            reason: "User '{$user->getLabel()}' (id: '{$user->getId()->value}') has role '{$userWorkspaceRole->value}' for workspace '{$workspaceName->value}'" . ($userIsAdministrator ? ' and is an Administrator' : ' and is no Administrator'),
        );
    }

    public function getVisibilityConstraintsForAnonymousUser(ContentRepositoryId $contentRepositoryId): VisibilityConstraints
    {
        $roles = array_map($this->policyService->getRole(...), [self::FLOW_ROLE_EVERYBODY, self::FLOW_ROLE_ANONYMOUS]);
        return $this->visibilityConstraintsForRoles($contentRepositoryId, $roles);
    }

    public function getVisibilityConstraintsForUser(ContentRepositoryId $contentRepositoryId, User $user): VisibilityConstraints
    {
        $userRoles = $this->rolesForUser($user);
        return $this->visibilityConstraintsForRoles($contentRepositoryId, $userRoles);
    }

    /**
     * @param array<Role> $roles
     */
    private function visibilityConstraintsForRoles(ContentRepositoryId $contentRepositoryId, array $roles): VisibilityConstraints
    {
        $restrictedSubtreeTags = [];
        /** @var SubtreeTagPrivilege $privilege */
        foreach ($this->policyService->getAllPrivilegesByType(SubtreeTagPrivilege::class) as $privilege) {
            if (!$this->privilegeManager->isGrantedForRoles($roles, SubtreeTagPrivilege::class, new SubtreeTagPrivilegeSubject($privilege->getSubtreeTag(), $contentRepositoryId))) {
                $restrictedSubtreeTags[] = $privilege->getSubtreeTag();
            }
        }
        return new VisibilityConstraints(SubtreeTags::fromArray($restrictedSubtreeTags));
    }

    /**
     * @return array<Role>
     */
    private function rolesForUser(User $user): array
    {
        try {
            $userRoles = $this->userService->getAllRoles($user);
        } catch (NoSuchRoleException $e) {
            throw new \RuntimeException("Failed to determine roles for user '{$user->getLabel()}' (id: '{$user->getId()->value}'), check your package dependencies: {$e->getMessage()}", 1727084881, $e);
        }
        return $userRoles;
    }
}
