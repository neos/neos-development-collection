<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Security;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\Security\Dto\Privilege;
use Neos\ContentRepository\Core\Feature\Security\Dto\UserId;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * A simple auth provider that just statically returns the same user id that it was given upon construction time and grants all privileges
 *
 * @api
 */
final class StaticAuthProvider implements AuthProviderInterface
{
    public function __construct(
        private readonly UserId $userId,
    ) {
    }

    public function getAuthenticatedUserId(): UserId
    {
        return $this->userId;
    }

    public function getVisibilityConstraints(WorkspaceName $workspaceName): VisibilityConstraints
    {
        return VisibilityConstraints::default();
    }

    public function canReadNodesFromWorkspace(WorkspaceName $workspaceName): Privilege
    {
        return Privilege::granted(self::class . ' always grants privileges');
    }

    public function canExecuteCommand(CommandInterface $command): Privilege
    {
        return Privilege::granted(self::class . ' always grants privileges');
    }
}
