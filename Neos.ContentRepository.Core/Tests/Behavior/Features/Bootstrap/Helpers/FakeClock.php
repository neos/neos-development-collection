<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final class FakeClock implements ClockInterface
{
    private static ?DateTimeImmutable $now = null;

    public static function setNow(DateTimeImmutable $now): void
    {
        self::$now = $now;
    }

    public function now(): DateTimeImmutable
    {
        return self::$now ?? new DateTimeImmutable();
    }
}
