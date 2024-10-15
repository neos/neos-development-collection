<?php

declare(strict_types=1);

namespace Neos\Neos\ContentRepositoryAuthProvider;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\AddDimensionShineThrough;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\MoveDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Command\CopyNodesRecursively;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetSerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\TagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\UntagSubtree;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\ChangeBaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\DeleteWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\SharedModel\Auth\AuthProviderInterface;
use Neos\ContentRepository\Core\SharedModel\Auth\Privilege;
use Neos\ContentRepository\Core\SharedModel\Auth\UserId;
use Neos\ContentRepository\Core\SharedModel\Auth\WorkspacePrivilegeType;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Neos\Domain\Model\WorkspacePermissions;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Domain\Service\WorkspaceService;

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

    public function getWorkspacePrivilege(WorkspaceName $workspaceName, WorkspacePrivilegeType $privilegeType): Privilege
    {
        if ($this->securityContext->areAuthorizationChecksDisabled()) {
            return Privilege::granted();
        }
        $workspacePermissions = $this->getWorkspacePermissionsForAuthenticatedUser($workspaceName);
        if ($workspacePermissions === null) {
            return Privilege::denied('No user is authenticated');
        }
        return match ($privilegeType) {
            WorkspacePrivilegeType::READ_NODES => $workspacePermissions->read ? Privilege::granted() : Privilege::denied(sprintf('User has no read permission for workspace "%s"', $workspaceName->value)),
        };
    }

    public function getCommandPrivilege(CommandInterface $command): Privilege
    {
        if ($this->securityContext->areAuthorizationChecksDisabled()) {
            return Privilege::granted();
        }
        if ($command instanceof CreateWorkspace) {
            $baseWorkspacePermissions = $this->getWorkspacePermissionsForAuthenticatedUser($command->baseWorkspaceName);
            if ($baseWorkspacePermissions === null || !$baseWorkspacePermissions->write) {
                return Privilege::denied(sprintf('no write permissions on base workspace "%s"', $command->baseWorkspaceName->value));
            }
            return Privilege::granted();
        }
        list($privilege, $workspaceName) = match ($command::class) {
            AddDimensionShineThrough::class,
            ChangeNodeAggregateName::class,
            ChangeNodeAggregateType::class,
            CopyNodesRecursively::class,
            CreateNodeAggregateWithNode::class,
            CreateNodeAggregateWithNodeAndSerializedProperties::class,
            CreateNodeVariant::class,
            CreateRootNodeAggregateWithNode::class,
            DisableNodeAggregate::class,
            DiscardIndividualNodesFromWorkspace::class,
            DiscardWorkspace::class,
            EnableNodeAggregate::class,
            MoveDimensionSpacePoint::class,
            MoveNodeAggregate::class,
            PublishIndividualNodesFromWorkspace::class,
            PublishWorkspace::class,
            RebaseWorkspace::class,
            RemoveNodeAggregate::class,
            SetNodeProperties::class,
            SetNodeReferences::class,
            SetSerializedNodeProperties::class,
            SetSerializedNodeReferences::class,
            TagSubtree::class,
            UntagSubtree::class,
            UpdateRootNodeAggregateDimensions::class => ['write', $command->workspaceName],
            ChangeBaseWorkspace::class,
            CreateRootWorkspace::class,
            CreateWorkspace::class,
            DeleteWorkspace::class => ['manage', $command->workspaceName],
            default => [null, null],
        };
        if ($privilege === null) {
            return Privilege::granted();
        }
        $workspacePermissions = $this->getWorkspacePermissionsForAuthenticatedUser($workspaceName);
        if ($workspacePermissions === null) {
            return Privilege::denied(sprintf('No user is authenticated to %s workspace "%s" because no user is authenticated', $privilege, $workspaceName->value));
        }
        $privilegeGranted = $privilege === 'write' ? $workspacePermissions->write : $workspacePermissions->manage;
        return $privilegeGranted ? Privilege::granted() : Privilege::denied(sprintf('User has no %s permission for workspace "%s"', $privilege, $workspaceName->value));
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
