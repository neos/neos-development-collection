<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Dbal;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Doctrine\DBAL\Exception as DBALException;

/**
 * Utility to acquire advisory locks from a mysql/mariadb database
 * @internal
 */
final class MysqlPlatformContentRepositoryLocker
{
    private function __construct(
        private string $crLockName,
        private Connection $dbal,
    ) {
    }

    public static function forContentRepositoryAndConnection(
        ContentRepositoryId $contentRepositoryId,
        Connection $connection,
    ): self {
        return new self(
            crLockName: sprintf('CR_%s', strtoupper($contentRepositoryId->value)),
            dbal: $connection,
        );
    }

    /**
     * Acquire a global lock for the content repository, if no lock could be acquired within timeoutInSeconds an exception is thrown
     *
     * @throws AcquiringLockFailed
     * @see releaseLock
     */
    public function acquireLock(int $timeoutInSeconds): void
    {
        try {
            $result = $this->dbal->executeQuery('SELECT GET_LOCK(:name, :timeoutInSeconds)', ['name' => $this->crLockName, 'timeoutInSeconds' => $timeoutInSeconds]);
        } catch (DBALException $exception) {
            throw AcquiringLockFailed::becauseConnectionException($this->crLockName, $exception);
        }
        if ((bool)$result->fetchOne() === false) {
            throw AcquiringLockFailed::becauseTimeoutExceeded($this->crLockName, $timeoutInSeconds);
        }
    }

    /**
     * Release a global lock for the content repository.
     *
     * @throws ReleasingLockFailed
     */
    public function releaseLock(): void
    {
        try {
            $this->dbal->executeStatement('RELEASE_LOCK(:name)', ['name' => $this->crLockName]);
        } catch (DBALException $exception) {
            throw ReleasingLockFailed::becauseConnectionException($this->crLockName, $exception);
        }
    }
}
