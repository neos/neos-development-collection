<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Fakes;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Clock implementation for tests
 * This is a mutable class in order to allow to adjust the behaviour during runtime for testing purposes
 */
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
