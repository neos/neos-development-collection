<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepositoryRegistry\Factory\Clock\ClockFactoryInterface;
use Psr\Clock\ClockInterface;

final class FakeClockFactory implements ClockFactoryInterface
{

    public function build(): ClockInterface
    {
        return new FakeClock();
    }
}
