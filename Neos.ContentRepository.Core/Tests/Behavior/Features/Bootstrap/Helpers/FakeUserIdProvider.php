<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;

final class FakeUserIdProvider implements UserIdProviderInterface
{
    private static ?UserId $userId = null;

    public static function setUserId(UserId $userId): void
    {
        self::$userId = $userId;
    }

    public function getUserId(): UserId
    {
        return self::$userId ?? UserId::forSystemUser();
    }
}
