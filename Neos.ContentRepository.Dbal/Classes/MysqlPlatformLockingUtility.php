<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;

/**
 * Utility to acquire advisory locks from a mysql/mariadb database
 * @internal
 */
final class MysqlPlatformLockingUtility
{
    private const GLOBAL_LOCK_NAME = 'NEOS_GLOBAL_LOCK';

    private function __construct()
    {
    }

    /**
     * Acquire a lock with the given name, if no lock could be acquired within timeoutInSeconds an exception is thrown
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws LockingException
     * @see releaseLock
     */
    public static function acquireLock(Connection $dbal, string $name, int $timeoutInSeconds): void
    {
        assert($dbal->getDatabasePlatform() instanceof AbstractMySQLPlatform);
        $result = $dbal->executeQuery('SELECT GET_LOCK(:name, :timeoutInSeconds)', ['name' => $name, 'timeoutInSeconds' => $timeoutInSeconds]);
        if ((bool)$result->fetchOne() === false) {
            throw new LockingException('Could not acquire lock for ' . $name . ' within ' . $timeoutInSeconds . 'seconds.', 1780665466038);
        }
    }

    /**
     * Release a lock with the given name.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function releaseLock(Connection $dbal, string $name): bool
    {
        assert($dbal->getDatabasePlatform() instanceof AbstractMySQLPlatform);
        $result = $dbal->executeQuery('SELECT RELEASE_LOCK(:name)', ['name' => $name]);
        return (bool)$result->fetchOne();
    }

    /**
     * Acquire a global Neos lock from the database
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws LockingException
     * @see acquireLock
     * @see releaseGlobalLock
     */
    public static function acquireGlobalLock(Connection $dbal, int $timeoutInSeconds): void
    {
        self::acquireLock($dbal, self::GLOBAL_LOCK_NAME, $timeoutInSeconds);
    }

    /**
     * Release the global Neos lock from the database
     * @throws \Doctrine\DBAL\Exception
     */
    public static function releaseGlobalLock(Connection $dbal): bool
    {
        return self::releaseLock($dbal, self::GLOBAL_LOCK_NAME);
    }
}
