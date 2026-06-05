<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Dbal;
use Doctrine\DBAL\Exception as DBALException;

/**
 * @internal
 */
class AcquiringLockFailed extends \RuntimeException
{
    public static function becauseTimeoutExceeded(string $name, int $timeoutInSeconds): self
    {
        return new self(sprintf('Could not acquire lock for %s within %s seconds.', $name, $timeoutInSeconds), 1780670522);
    }

    public static function becauseConnectionException(string $name, DBALException $connectionException): self
    {
        return new self(sprintf('Could not acquire lock for %s: %s', $name, $connectionException->getMessage()), 1780671125, $connectionException);
    }
}
