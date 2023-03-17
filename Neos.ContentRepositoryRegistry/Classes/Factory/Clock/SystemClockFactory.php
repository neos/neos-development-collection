<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\Clock;

use Psr\Clock\ClockInterface;

final class SystemClockFactory implements ClockFactoryInterface
{
    public function build(): ClockInterface
    {
        return new SystemClock();
    }
}
