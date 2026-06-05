<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Dbal;

/**
 * @internal
 */
class AcquiringLockFailed extends \RuntimeException
{
    public static function becauseTimeoutExceeded(string $name, int $timeoutInSeconds): self
    {
        return new self(sprintf('Could not acquire lock for %s within %s seconds.', $name, $timeoutInSeconds), 1780670522);
    }
}
