<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\Clock;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * @internal
 */
final class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
