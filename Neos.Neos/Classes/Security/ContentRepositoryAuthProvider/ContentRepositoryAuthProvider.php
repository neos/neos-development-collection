<?php

declare(strict_types=1);

namespace Neos\Neos\Security\ContentRepositoryAuthProvider;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\Security\AuthProviderInterface;
use Neos\ContentRepository\Core\Feature\Security\Dto\Privilege;
use Neos\ContentRepository\Core\Feature\Security\Dto\UserId;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\ChangeBaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\DeleteWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Neos\Domain\Model\WorkspacePermissions;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Security\Authorization\ContentRepositoryAuthorizationService;

/**
 * Implementation of Content Repository {@see AuthProviderInterface} which ties the authorization
 * to Neos.
 *
 * @internal use {@see ContentRepositoryAuthorizationService} to ask for specific authorization decisions
 */
final class ContentRepositoryAuthProvider implements AuthProviderInterface
{
    private const WORKSPACE_PERMISSION_WRITE = 'write';
    private const WORKSPACE_PERMISSION_MANAGE = 'manage';

    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly UserService $userService,
        private readonly ContentRepositoryAuthorizationService $authorizationService,
        private readonly SecurityContext $securityContext,
    ) {
    }

    public function getAuthenticatedUserId(): ?UserId
    {
        $user = $this->userService->getCurrentUser();
        if ($user === null) {
            return null;
        }
        return UserId::fromString($user->getId()->value);
    }

    public function getVisibilityConstraints(WorkspaceName $workspaceName): VisibilityConstraints
    {
        $authenticatedAccount = $this->securityContext->getAccount();
        if ($authenticatedAccount) {
            return $this->authorizationService->getVisibilityConstraintsForAccount($this->contentRepositoryId, $authenticatedAccount);
        }
        return $this->authorizationService->getVisibilityConstraintsForAnonymousUser($this->contentRepositoryId);
    }

    public function canReadNodesFromWorkspace(WorkspaceName $workspaceName): Privilege
    {
        if ($this->securityContext->areAuthorizationChecksDisabled()) {
            return Privilege::granted('Authorization checks are disabled');
        }
        $authenticatedAccount = $this->securityContext->getAccount();
        if ($authenticatedAccount === null) {
            $workspacePermissions = $this->authorizationService->getWorkspacePermissionsForAnonymousUser($this->contentRepositoryId, $workspaceName);
        } else {
            $workspacePermissions = $this->authorizationService->getWorkspacePermissionsForAccount($this->contentRepositoryId, $workspaceName, $authenticatedAccount);
        }
        return $workspacePermissions->read ? Privilege::granted($workspacePermissions->getReason()) : Privilege::denied($workspacePermissions->getReason());
    }

    public function canExecuteCommand(CommandInterface $command): Privilege
    {
        if ($this->securityContext->areAuthorizationChecksDisabled()) {
            return Privilege::granted('Authorization checks are disabled');
        }

        // Note: We check against the {@see RebasableToOtherWorkspaceInterface} because that is implemented by all
        // commands that interact with nodes on a content stream. With that it's likely that we don't have to adjust the
        // code if we were to add new commands in the future
        if ($command instanceof RebasableToOtherWorkspaceInterface) {
            return $this->requireWorkspacePermission($command->getWorkspaceName(), self::WORKSPACE_PERMISSION_WRITE);
        }

        if ($command instanceof CreateRootWorkspace) {
            return Privilege::denied('Creation of root workspaces is currently only allowed with disabled authorization checks');
        }

        if ($command instanceof ChangeBaseWorkspace) {
            $workspacePermissions = $this->getWorkspacePermissionsForCurrentUser($command->workspaceName);
            if (!$workspacePermissions->manage) {
                return Privilege::denied("Missing 'manage' permissions for workspace '{$command->workspaceName->value}': {$workspacePermissions->getReason()}");
            }
            $baseWorkspacePermissions = $this->getWorkspacePermissionsForCurrentUser($command->baseWorkspaceName);
            if (!$baseWorkspacePermissions->write) {
                return Privilege::denied("Missing 'write' permissions for base workspace '{$command->baseWorkspaceName->value}': {$baseWorkspacePermissions->getReason()}");
            }
            return Privilege::granted("User has 'manage' permissions for workspace '{$command->workspaceName->value}' and 'write' permissions for base workspace '{$command->baseWorkspaceName->value}'");
        }
        return match ($command::class) {
            CreateWorkspace::class => $this->requireWorkspacePermission($command->baseWorkspaceName, self::WORKSPACE_PERMISSION_WRITE),
            DeleteWorkspace::class => $this->requireWorkspacePermission($command->workspaceName, self::WORKSPACE_PERMISSION_MANAGE),
            DiscardWorkspace::class,
            DiscardIndividualNodesFromWorkspace::class,
            PublishWorkspace::class,
            PublishIndividualNodesFromWorkspace::class,
            RebaseWorkspace::class => $this->requireWorkspacePermission($command->workspaceName, self::WORKSPACE_PERMISSION_WRITE),
            default => Privilege::granted('Command not restricted'),
        };
    }

    private function requireWorkspacePermission(WorkspaceName $workspaceName, string $permission): Privilege
    {
        $workspacePermissions = $this->getWorkspacePermissionsForCurrentUser($workspaceName);
        if (!$workspacePermissions->{$permission}) {
            return Privilege::denied("Missing '{$permission}' permissions for workspace '{$workspaceName->value}': {$workspacePermissions->getReason()}");
        }
        return Privilege::granted("User has '{$permission}' permissions for workspace '{$workspaceName->value}'");
    }

    private function getWorkspacePermissionsForCurrentUser(WorkspaceName $workspaceName): WorkspacePermissions
    {
        $authenticatedAccount = $this->securityContext->getAccount();
        if ($authenticatedAccount === null) {
            return $this->authorizationService->getWorkspacePermissionsForAnonymousUser($this->contentRepositoryId, $workspaceName);
        }
        return $this->authorizationService->getWorkspacePermissionsForAccount($this->contentRepositoryId, $workspaceName, $authenticatedAccount);
    }
}
