<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Auth;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @internal except for CR factory implementations
 */
interface AuthProviderInterface
{
    public function getUserId(): UserId;

    public function getReadNodesFromWorkspacePrivilege(WorkspaceName $workspaceName): Privilege;

    public function getVisibilityConstraints(WorkspaceName $workspaceName): VisibilityConstraints;

    public function getCommandPrivilege(CommandInterface $command): Privilege;
}
