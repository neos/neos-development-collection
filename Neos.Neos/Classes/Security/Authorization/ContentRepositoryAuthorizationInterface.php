<?php

declare(strict_types=1);

namespace Neos\Neos\Security\Authorization;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Policy\Role;
use Neos\Neos\Domain\Model\NodePermissions;
use Neos\Neos\Domain\Model\UserId;
use Neos\Neos\Domain\Model\WorkspacePermissions;

/**
 * Central point which does ContentRepository authorization decisions within Neos.
 *
 * @api but you might need to implement additional methods in future Neos versions
 */
interface ContentRepositoryAuthorizationInterface
{

    /**
     * Determines the {@see WorkspacePermissions} a user with the specified {@see Role}s has for the specified workspace
     *
     * @param array<Role> $roles The {@see Role} instances to check access for. Note: These have to be the expanded roles auf the authenticated tokens {@see Context::getRoles()}
     * @param UserId|null $userId Optional ID of the authenticated Neos user. If set the workspace owner is evaluated since owners always have all permissions on their workspace
     */
    public function getWorkspacePermissions(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, array $roles, UserId|null $userId): WorkspacePermissions;

    /**
     * Determines the {@see NodePermissions} a user with the specified {@see Role}s has on the given {@see Node}
     *
     * @param array<Role> $roles
     */
    public function getNodePermissions(Node $node, array $roles): NodePermissions;

    /**
     * Determines the default {@see VisibilityConstraints} for the specified {@see Role}s
     *
     * @param array<Role> $roles
     */
    public function getVisibilityConstraints(ContentRepositoryId $contentRepositoryId, array $roles): VisibilityConstraints;
}
