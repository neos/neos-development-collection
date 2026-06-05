<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Dbal;

use Doctrine\DBAL\Exception as DBALException;

/**
 * @internal
 */
class ReleasingLockFailed extends \RuntimeException
{
    public static function becauseConnectionException(string $name, DBALException $connectionException): self
    {
        return new self(sprintf('Could not release lock for %s: %s', $name, $connectionException->getMessage()), 1780671147, $connectionException);
    }
}
