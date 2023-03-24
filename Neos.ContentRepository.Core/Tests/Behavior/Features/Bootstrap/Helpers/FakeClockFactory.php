<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Factory\Clock\ClockFactoryInterface;
use Psr\Clock\ClockInterface;

final class FakeClockFactory implements ClockFactoryInterface
{

    public function build(ContentRepositoryId $contentRepositoryIdentifier, array $contentRepositorySettings, array $contentRepositoryPreset): ClockInterface
    {
        return new FakeClock();
    }
}
