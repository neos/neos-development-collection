<?php

declare(strict_types=1);

namespace Neos\Neos\Security\ContentRepositoryAuthProvider;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\AddDimensionShineThrough;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\MoveDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\Feature\Security\AuthProviderInterface;
use Neos\ContentRepository\Core\Feature\Security\Dto\Privilege;
use Neos\ContentRepository\Core\Feature\Security\Dto\UserId;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\TagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\UntagSubtree;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\BaseWorkspaceDoesNotExist;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\ChangeBaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\DeleteWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceHasNoBaseWorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Neos\Domain\Model\WorkspacePermissions;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Security\Authorization\ContentRepositoryAuthorizationService;
use Neos\Neos\Security\Authorization\Privilege\EditNodePrivilege;

/**
 * Implementation of Content Repository {@see AuthProviderInterface} which ties the authorization
 * to Neos.
 *
 * @internal use {@see ContentRepositoryAuthorizationService} to ask for specific authorization decisions
 */
final readonly class ContentRepositoryAuthProvider implements AuthProviderInterface
{
    public function __construct(
        private ContentRepositoryId $contentRepositoryId,
        private UserService $userService,
        private ContentGraphReadModelInterface $contentGraphReadModel,
        private ContentRepositoryAuthorizationService $authorizationService,
        private SecurityContext $securityContext,
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
        return $this->authorizationService->getVisibilityConstraints($this->contentRepositoryId, $this->securityContext->getRoles());
    }

    public function canReadNodesFromWorkspace(WorkspaceName $workspaceName): Privilege
    {
        if ($this->securityContext->areAuthorizationChecksDisabled()) {
            return Privilege::granted('Authorization checks are disabled');
        }
        $workspacePermissions = $this->authorizationService->getWorkspacePermissions(
            $this->contentRepositoryId,
            $workspaceName,
            $this->securityContext->getRoles(),
            $this->userService->getCurrentUser()?->getId(),
        );
        return $workspacePermissions->read ? Privilege::granted($workspacePermissions->getReason()) : Privilege::denied($workspacePermissions->getReason());
    }

    public function canExecuteCommand(CommandInterface $command): Privilege
    {
        if ($this->securityContext->areAuthorizationChecksDisabled()) {
            return Privilege::granted('Authorization checks are disabled');
        }
        $nodeThatRequiresEditPrivilege = $this->nodeThatRequiresEditPrivilegeForCommand($command);
        if ($nodeThatRequiresEditPrivilege !== null) {
            $workspacePermissions = $this->getWorkspacePermissionsForCurrentUser($nodeThatRequiresEditPrivilege->workspaceName);
            if (!$workspacePermissions->write) {
                return Privilege::denied(sprintf('No write permissions on workspace "%s": %s', $nodeThatRequiresEditPrivilege->workspaceName->value, $workspacePermissions->getReason()));
            }
            $node = $this->contentGraphReadModel
                ->getContentGraph($nodeThatRequiresEditPrivilege->workspaceName)
                ->getSubgraph($nodeThatRequiresEditPrivilege->dimensionSpacePoint, VisibilityConstraints::createEmpty())
                ->findNodeById($nodeThatRequiresEditPrivilege->aggregateId);
            if ($node === null) {
                return Privilege::denied(sprintf('Failed to load node "%s" in workspace "%s"', $nodeThatRequiresEditPrivilege->aggregateId->value, $nodeThatRequiresEditPrivilege->workspaceName->value));
            }
            $nodePermissions = $this->authorizationService->getNodePermissions($node, $this->securityContext->getRoles());
            if (!$nodePermissions->edit) {
                return Privilege::denied(sprintf('No edit permissions for node "%s" in workspace "%s": %s', $nodeThatRequiresEditPrivilege->aggregateId->value, $nodeThatRequiresEditPrivilege->workspaceName->value, $nodePermissions->getReason()));
            }
            return Privilege::granted(sprintf('Edit permissions for node "%s" in workspace "%s" granted: %s', $nodeThatRequiresEditPrivilege->aggregateId->value, $nodeThatRequiresEditPrivilege->workspaceName->value, $nodePermissions->getReason()));
        }
        if ($command instanceof CreateRootWorkspace) {
            return Privilege::denied('Creation of root workspaces is currently only allowed with disabled authorization checks');
        }
        if ($command instanceof CreateWorkspace) {
            $baseWorkspacePermissions = $this->getWorkspacePermissionsForCurrentUser($command->baseWorkspaceName);
            if (!$baseWorkspacePermissions->read) {
                return Privilege::denied(sprintf('Missing "read" permissions for base workspace "%s": %s', $command->baseWorkspaceName->value, $baseWorkspacePermissions->getReason()));
            }
            return Privilege::granted(sprintf('User has "read" permissions for base workspace "%s"', $command->baseWorkspaceName->value));
        }
        if ($command instanceof ChangeBaseWorkspace) {
            $workspacePermissions = $this->getWorkspacePermissionsForCurrentUser($command->workspaceName);
            if (!$workspacePermissions->manage) {
                return Privilege::denied(sprintf('Missing "manage" permissions for workspace "%s": %s', $command->workspaceName->value, $workspacePermissions->getReason()));
            }
            $baseWorkspacePermissions = $this->getWorkspacePermissionsForCurrentUser($command->baseWorkspaceName);
            if (!$baseWorkspacePermissions->read) {
                return Privilege::denied(sprintf('Missing "read" permissions for base workspace "%s": %s', $command->baseWorkspaceName->value, $baseWorkspacePermissions->getReason()));
            }
            return Privilege::granted(sprintf('User has "manage" permissions for workspace "%s" and "read" permissions for base workspace "%s"', $command->workspaceName->value, $command->baseWorkspaceName->value));
        }
        if ($command instanceof PublishWorkspace || $command instanceof PublishIndividualNodesFromWorkspace) {
            $workspacePermissions = $this->getWorkspacePermissionsForCurrentUser($command->workspaceName);
            if (!$workspacePermissions->write) {
                return Privilege::denied(sprintf('Missing "write" permissions for workspace "%s": %s', $command->workspaceName->value, $workspacePermissions->getReason()));
            }
            $workspace = $this->contentGraphReadModel->findWorkspaceByName($command->workspaceName);
            if ($workspace === null) {
                throw WorkspaceDoesNotExist::butWasSupposedTo($command->workspaceName);
            }
            if ($workspace->baseWorkspaceName === null) {
                throw WorkspaceHasNoBaseWorkspaceName::butWasSupposedTo($workspace->workspaceName);
            }
            $baseWorkspace = $this->contentGraphReadModel->findWorkspaceByName($workspace->baseWorkspaceName);
            if ($baseWorkspace === null) {
                throw BaseWorkspaceDoesNotExist::butWasSupposedTo($workspace->workspaceName);
            }
            $baseWorkspacePermissions = $this->getWorkspacePermissionsForCurrentUser($baseWorkspace->workspaceName);
            if (!$baseWorkspacePermissions->write) {
                return Privilege::denied(sprintf('Missing "write" permissions for base workspace "%s": %s', $baseWorkspace->workspaceName->value, $baseWorkspacePermissions->getReason()));
            }
            return Privilege::granted(sprintf('User has "manage" permissions for workspace "%s" and "write" permissions for base workspace "%s"', $command->workspaceName->value, $baseWorkspace->workspaceName->value));
        }
        return match ($command::class) {
            AddDimensionShineThrough::class,
            ChangeNodeAggregateName::class,
            ChangeNodeAggregateType::class,
            CreateRootNodeAggregateWithNode::class,
            MoveDimensionSpacePoint::class,
            UpdateRootNodeAggregateDimensions::class,
            DiscardWorkspace::class,
            DiscardIndividualNodesFromWorkspace::class,
            RebaseWorkspace::class => $this->requireWorkspaceWritePermission($command->workspaceName),
            DeleteWorkspace::class => $this->requireWorkspaceManagePermission($command->workspaceName),
            default => Privilege::granted('Command not restricted'),
        };
    }

    /**
     * For a given command, determine the node (represented as {@see NodeAddress}) that needs {@see EditNodePrivilege} to be granted
     */
    private function nodeThatRequiresEditPrivilegeForCommand(CommandInterface $command): ?NodeAddress
    {
        return match ($command::class) {
            CreateNodeAggregateWithNode::class => NodeAddress::create($this->contentRepositoryId, $command->workspaceName, $command->originDimensionSpacePoint->toDimensionSpacePoint(), $command->parentNodeAggregateId),
            CreateNodeVariant::class => NodeAddress::create($this->contentRepositoryId, $command->workspaceName, $command->sourceOrigin->toDimensionSpacePoint(), $command->nodeAggregateId),
            RemoveNodeAggregate::class,
            TagSubtree::class,
            UntagSubtree::class => NodeAddress::create($this->contentRepositoryId, $command->workspaceName, $command->coveredDimensionSpacePoint, $command->nodeAggregateId),
            MoveNodeAggregate::class => NodeAddress::create($this->contentRepositoryId, $command->workspaceName, $command->dimensionSpacePoint, $command->nodeAggregateId),
            SetNodeProperties::class => NodeAddress::create($this->contentRepositoryId, $command->workspaceName, $command->originDimensionSpacePoint->toDimensionSpacePoint(), $command->nodeAggregateId),
            SetNodeReferences::class => NodeAddress::create($this->contentRepositoryId, $command->workspaceName, $command->sourceOriginDimensionSpacePoint->toDimensionSpacePoint(), $command->sourceNodeAggregateId),
            default => null,
        };
    }

    private function requireWorkspaceWritePermission(WorkspaceName $workspaceName): Privilege
    {
        $workspacePermissions = $this->getWorkspacePermissionsForCurrentUser($workspaceName);
        if (!$workspacePermissions->write) {
            return Privilege::denied("Missing 'write' permissions for workspace '{$workspaceName->value}': {$workspacePermissions->getReason()}");
        }
        return Privilege::granted("User has 'write' permissions for workspace '{$workspaceName->value}'");
    }

    private function requireWorkspaceManagePermission(WorkspaceName $workspaceName): Privilege
    {
        $workspacePermissions = $this->getWorkspacePermissionsForCurrentUser($workspaceName);
        if (!$workspacePermissions->manage) {
            return Privilege::denied("Missing 'manage' permissions for workspace '{$workspaceName->value}': {$workspacePermissions->getReason()}");
        }
        return Privilege::granted("User has 'manage' permissions for workspace '{$workspaceName->value}'");
    }

    private function getWorkspacePermissionsForCurrentUser(WorkspaceName $workspaceName): WorkspacePermissions
    {
        return $this->authorizationService->getWorkspacePermissions(
            $this->contentRepositoryId,
            $workspaceName,
            $this->securityContext->getRoles(),
            $this->userService->getCurrentUser()?->getId(),
        );
    }
}
