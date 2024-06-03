<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\Clock;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Psr\Clock\ClockInterface;

/**
 * @api
 */
final class SystemClockFactory implements ClockFactoryInterface
{
    /** @param array<string, mixed> $options */
    public function build(ContentRepositoryId $contentRepositoryIdentifier, array $options): ClockInterface
    {
        return new SystemClock();
    }
}
