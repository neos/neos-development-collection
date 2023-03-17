<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers;

use DateTimeImmutable;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;
use Neos\ContentRepositoryRegistry\Factory\UserIdProvider\UserIdProviderFactoryInterface;

final class FakeUserIdProviderFactory implements UserIdProviderFactoryInterface
{
    private static ?DateTimeImmutable $now = null;

    public function __construct()
    {
        if (self::$now === null) {
            self::$now = new DateTimeImmutable();
        }
    }

    public static function setNow(DateTimeImmutable $now): void
    {
        self::$now = $now;
    }

    public static function getNow(): DateTimeImmutable
    {
        return self::$now ?? new DateTimeImmutable();
    }

    public function build(ContentRepositoryId $contentRepositoryIdentifier, array $contentRepositorySettings, array $projectionCatchUpTriggerPreset): UserIdProviderInterface
    {
        return new FakeUserIdProvider();
    }
}
