<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Auth;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @internal except for CR factory implementations
 */
interface AuthProviderInterface
{
    public function getUserId(): UserId;

    public function getWorkspacePrivilege(WorkspaceName $workspaceName, WorkspacePrivilegeType $privilegeType): Privilege;

    public function getCommandPrivilege(CommandInterface $command): Privilege;
}
