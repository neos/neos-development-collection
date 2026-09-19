<?php

declare(strict_types=1);

namespace Neos\Neos\Security\Authorization;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Policy\Role;
use Neos\Neos\Domain\Model\NodePermissions;
use Neos\Neos\Domain\Model\UserId;
use Neos\Neos\Domain\Model\WorkspacePermissions;
use Neos\Neos\Domain\Model\WorkspaceRole;
use Neos\Neos\Domain\Model\WorkspaceRoleSubject;
use Neos\Neos\Domain\Model\WorkspaceRoleSubjects;
use Neos\Neos\Domain\Repository\WorkspaceMetadataAndRoleRepository;
use Neos\Neos\Domain\SubtreeTagging\NeosVisibilityConstraints;
use Neos\Neos\Security\Authorization\Privilege\EditNodePrivilege;
use Neos\Neos\Security\Authorization\Privilege\ReadNodePrivilege;
use Neos\Neos\Security\Authorization\Privilege\SubtreeTagPrivilegeSubject;

/**
 * Central point which does ContentRepository authorization decisions within Neos.
 *
 * @api
 */
#[Flow\Scope('singleton')]
final readonly class ContentRepositoryAuthorizationService
{
    private const ROLE_NEOS_ADMINISTRATOR = 'Neos.Neos:Administrator';

    public function __construct(
        private WorkspaceMetadataAndRoleRepository $metadataAndRoleRepository,
        private PolicyService $policyService,
        private PrivilegeManagerInterface $privilegeManager,
    ) {
    }

    /**
     * Determines the {@see WorkspacePermissions} a user with the specified {@see Role}s has for the specified workspace
     *
     * @param array<Role> $roles The {@see Role} instances to check access for. Note: These have to be the expanded roles auf the authenticated tokens {@see Context::getRoles()}
     * @param UserId|null $userId Optional ID of the authenticated Neos user. If set the workspace owner is evaluated since owners always have all permissions on their workspace
     */
    public function getWorkspacePermissions(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, array $roles, UserId|null $userId): WorkspacePermissions
    {
        $workspaceMetadata = $this->metadataAndRoleRepository->loadWorkspaceMetadata($contentRepositoryId, $workspaceName);
        if ($userId !== null && $workspaceMetadata?->ownerUserId !== null && $userId->equals($workspaceMetadata->ownerUserId)) {
            return WorkspacePermissions::all(sprintf('User with id "%s" is the owner of workspace "%s"', $userId->value, $workspaceName->value));
        }
        $roleIdentifiers = array_map(static fn (Role $role) => $role->getIdentifier(), array_values($roles));
        $subjects = array_map(WorkspaceRoleSubject::createForGroup(...), $roleIdentifiers);
        if ($userId !== null) {
            $subjects[] = WorkspaceRoleSubject::createForUser($userId);
        }
        /**
         * We hardcode the check against administrators to always grant manage permissions. This is done to allow administrators to fix permissions of all workspaces.
         * We don't allow all rights like read and write. Admins should be able to grant themselves permissions to write to other personal workspaces, but they should not have this permission automagically.
         */
        $userIsAdministrator = in_array(self::ROLE_NEOS_ADMINISTRATOR, $roleIdentifiers, true);
        $userWorkspaceRole = $this->metadataAndRoleRepository->getMostPrivilegedWorkspaceRoleForSubjects($contentRepositoryId, $workspaceName, WorkspaceRoleSubjects::fromArray($subjects));
        if ($userWorkspaceRole === null) {
            if ($userIsAdministrator) {
                return WorkspacePermissions::manage(sprintf('User is a Neos Administrator without explicit role for workspace "%s"', $workspaceName->value));
            }
            return WorkspacePermissions::none(sprintf('User is no Neos Administrator and has no explicit role for workspace "%s"', $workspaceName->value));
        }
        return WorkspacePermissions::create(
            read: $userWorkspaceRole->isAtLeast(WorkspaceRole::VIEWER),
            write: $userWorkspaceRole->isAtLeast(WorkspaceRole::COLLABORATOR),
            manage: $userIsAdministrator || $userWorkspaceRole->isAtLeast(WorkspaceRole::MANAGER),
            reason: sprintf('User is %s Neos Administrator and has role "%s" for workspace "%s"', $userIsAdministrator ? 'a' : 'no', $userWorkspaceRole->value, $workspaceName->value),
        );
    }

    /**
     * Determines the {@see NodePermissions} a user with the specified {@see Role}s has on the given {@see Node}
     *
     * @param array<Role> $roles
     */
    public function getNodePermissions(Node $node, array $roles): NodePermissions
    {
        $subtreeTagPrivilegeSubject = new SubtreeTagPrivilegeSubject($node->tags->all(), $node->contentRepositoryId);
        $readGranted = $this->privilegeManager->isGrantedForRoles($roles, ReadNodePrivilege::class, $subtreeTagPrivilegeSubject, $readReason);
        $writeGranted = $this->privilegeManager->isGrantedForRoles($roles, EditNodePrivilege::class, $subtreeTagPrivilegeSubject, $writeReason);
        return NodePermissions::create(
            read: $readGranted,
            edit: $writeGranted,
            reason: $readReason . "\n" . $writeReason,
        );
    }

    /**
     * Determines the default {@see VisibilityConstraints} for the specified {@see Role}s
     *
     * @param array<Role> $roles
     */
    public function getVisibilityConstraints(ContentRepositoryId $contentRepositoryId, array $roles): VisibilityConstraints
    {
        // soft removals are never visible by default
        $restrictedSubtreeTags = NeosVisibilityConstraints::excludeRemoved()->excludedSubtreeTags;
        /** @var ReadNodePrivilege $privilege */
        foreach ($this->policyService->getAllPrivilegesByType(ReadNodePrivilege::class) as $privilege) {
            if (!$this->privilegeManager->isGrantedForRoles($roles, ReadNodePrivilege::class, new SubtreeTagPrivilegeSubject($privilege->getSubtreeTags(), $contentRepositoryId))) {
                $restrictedSubtreeTags = $restrictedSubtreeTags->merge($privilege->getSubtreeTags());
            }
        }
        return VisibilityConstraints::excludeSubtreeTags($restrictedSubtreeTags);
    }
}
