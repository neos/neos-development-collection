<?php

declare(strict_types=1);

namespace Neos\Neos\Security\ContentRepositoryAuthProvider;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Auth\AuthProviderInterface;
use Neos\ContentRepository\Core\SharedModel\Auth\Privilege;
use Neos\ContentRepository\Core\SharedModel\Auth\UserId;
use Neos\ContentRepository\Core\SharedModel\Auth\WorkspacePrivilegeType;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Neos\Domain\Model\WorkspacePermissions;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Domain\Service\WorkspaceService;
use Neos\Neos\Security\Authorization\Privilege\SubtreeTagPrivilege;

/**
 * @api
 */
final class ContentRepositoryAuthProvider implements AuthProviderInterface
{
    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly UserService $userService,
        private readonly WorkspaceService $workspaceService,
        private readonly SecurityContext $securityContext,
        private readonly PolicyService $policyService,
    ) {
    }

    public function getUserId(): UserId
    {
        $user = $this->userService->getCurrentUser();
        if ($user === null) {
            return UserId::forSystemUser();
        }
        return UserId::fromString($user->getId()->value);
    }

    public function getVisibilityConstraints(WorkspaceName $workspaceName): VisibilityConstraints
    {
        if ($this->securityContext->areAuthorizationChecksDisabled()) {
            return VisibilityConstraints::default();
        }
        $restrictedSubtreeTags = [SubtreeTag::disabled()];
        try {
            /** @var array<SubtreeTagPrivilege> $subtreeTagPrivileges */
            $subtreeTagPrivileges = $this->policyService->getAllPrivilegesByType(SubtreeTagPrivilege::class);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Failed to determine SubtreeTag privileges: %s', $e->getMessage()), 1729180655, $e);
        }
        foreach ($subtreeTagPrivileges as $privilege) {
            if ($privilege->isGranted()) {
                continue;
            }
            $subtreeTag = $privilege->getParsedMatcher();
            if (str_contains($subtreeTag, ':')) {
                [$contentRepositoryId, $subtreeTag] = explode(':', $subtreeTag);
                if ($this->contentRepositoryId->value !== $contentRepositoryId) {
                    continue;
                }
            }
            $restrictedSubtreeTags[] = SubtreeTag::fromString($subtreeTag);
        }
        return new VisibilityConstraints(
            SubtreeTags::fromArray($restrictedSubtreeTags)
        );
    }

    public function getReadNodesFromWorkspacePrivilege(WorkspaceName $workspaceName): Privilege
    {
        if ($this->securityContext->areAuthorizationChecksDisabled()) {
            return Privilege::granted();
        }
        $user = $this->userService->getCurrentUser();
        if ($user === null) {
            return $workspaceName->isLive() ? Privilege::granted() : Privilege::denied('No user is authenticated');
        }
        $workspacePermissions = $this->workspaceService->getWorkspacePermissionsForUser($this->contentRepositoryId, $workspaceName, $user);
        return $workspacePermissions->read ? Privilege::granted() : Privilege::denied(sprintf('User "%s" (id: %s) has no read permission for workspace "%s"', $user->getLabel(), $user->getId()->value, $workspaceName->value));
    }

    public function getCommandPrivilege(CommandInterface $command): Privilege
    {
        if ($this->securityContext->areAuthorizationChecksDisabled()) {
            return Privilege::granted();
        }
        // TODO handle:
        // ChangeBaseWorkspace
        // CreateRootWorkspace
        // DeleteWorkspace
        // DiscardIndividualNodesFromWorkspace
        // DiscardWorkspace
        // PublishWorkspace
        // PublishIndividualNodesFromWorkspace
        // RebaseWorkspace
        if ($command instanceof CreateWorkspace) {
            $baseWorkspacePermissions = $this->getWorkspacePermissionsForAuthenticatedUser($command->baseWorkspaceName);
            if ($baseWorkspacePermissions === null || !$baseWorkspacePermissions->write) {
                return Privilege::denied(sprintf('no write permissions on base workspace "%s"', $command->baseWorkspaceName->value));
            }
            return Privilege::granted();
        }
        // Note: We check against the {@see RebasableToOtherWorkspaceInterface} because that is implemented by all
        // commands that interact with nodes on a content stream. With that it's likely that we don't have to adjust the
        // code if we were to add new commands in the future
        if (!$command instanceof RebasableToOtherWorkspaceInterface) {
            return Privilege::granted();
        }
        $user = $this->userService->getCurrentUser();
        if ($user === null) {
            return Privilege::denied('No user is authenticated');
        }
        $workspacePermissions = $this->workspaceService->getWorkspacePermissionsForUser($this->contentRepositoryId, $command->getWorkspaceName(), $user);
        return $workspacePermissions->write ? Privilege::granted() : Privilege::denied(sprintf('User "%s" (id: %s) has no write permission for workspace "%s"', $user->getLabel(), $user->getId()->value, $command->getWorkspaceName()->value));
    }

    private function getWorkspacePermissionsForAuthenticatedUser(WorkspaceName $workspaceName): ?WorkspacePermissions
    {
        $user = $this->userService->getCurrentUser();
        if ($user === null) {
            return null;
        }
        return $this->workspaceService->getWorkspacePermissionsForUser($this->contentRepositoryId, $workspaceName, $user);
    }
}
