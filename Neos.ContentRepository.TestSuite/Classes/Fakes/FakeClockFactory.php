<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Fakes;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Factory\Clock\ClockFactoryInterface;
use Psr\Clock\ClockInterface;

final class FakeClockFactory implements ClockFactoryInterface
{
    /**
     * @param array<string,mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryIdentifier, array $options): ClockInterface
    {
        return new FakeClock();
    }
}
