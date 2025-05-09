<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Fakes;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\Security\Dto\Privilege;
use Neos\ContentRepository\Core\Feature\Security\Dto\UserId;
use Neos\ContentRepository\Core\Feature\Security\AuthProviderInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Neos\Security\ContentRepositoryAuthProvider\ContentRepositoryAuthProvider;

/**
 * Content Repository AuthProvider implementation for tests
 * This is a mutable class in order to allow to adjust the behaviour during runtime for testing purposes
 */
final class FakeAuthProvider implements AuthProviderInterface
{
    private static ?UserId $userId = null;

    private static ?ContentRepositoryAuthProvider $contentRepositoryAuthProvider = null;

    public static function setDefaultUserId(UserId $userId): void
    {
        self::$userId = $userId;
    }

    public static function replaceAuthProvider(ContentRepositoryAuthProvider $contentRepositoryAuthProvider): void
    {
        self::$contentRepositoryAuthProvider = $contentRepositoryAuthProvider;
    }

    public static function resetAuthProvider(): void
    {
        self::$contentRepositoryAuthProvider = null;
    }

    public function getAuthenticatedUserId(): ?UserId
    {
        if (self::$contentRepositoryAuthProvider !== null) {
            return self::$contentRepositoryAuthProvider->getAuthenticatedUserId();
        }

        return self::$userId ?? null;
    }

    public function getVisibilityConstraints(WorkspaceName $workspaceName): VisibilityConstraints
    {
        if (self::$contentRepositoryAuthProvider !== null) {
            return self::$contentRepositoryAuthProvider->getVisibilityConstraints($workspaceName);
        }

        return VisibilityConstraints::createEmpty();
    }

    public function canReadNodesFromWorkspace(WorkspaceName $workspaceName): Privilege
    {
        if (self::$contentRepositoryAuthProvider !== null) {
            return self::$contentRepositoryAuthProvider->canReadNodesFromWorkspace($workspaceName);
        }

        return Privilege::granted(self::class . ' always grants privileges');
    }

    public function canExecuteCommand(CommandInterface $command): Privilege
    {
        if (self::$contentRepositoryAuthProvider !== null) {
            return self::$contentRepositoryAuthProvider->canExecuteCommand($command);
        }

        return Privilege::granted(self::class . ' always grants privileges');
    }
}
