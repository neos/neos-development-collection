<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\TestSuite\Behavior;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\FakeClock;
use Neos\ContentRepositoryRegistry\Factory\Clock\ClockFactoryInterface;
use Psr\Clock\ClockInterface;

final class FakeClockFactory implements ClockFactoryInterface
{
    public function build(ContentRepositoryId $contentRepositoryIdentifier, array $options): ClockInterface
    {
        return new FakeClock();
    }
}
