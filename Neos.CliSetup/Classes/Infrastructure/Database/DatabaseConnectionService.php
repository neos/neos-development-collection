<?php
declare(strict_types=1);

namespace Neos\CliSetup\Infrastructure\Database;

/*
 * This file is part of the Neos.CliSetup package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Neos\CliSetup\Exception as SetupException;

class DatabaseConnectionService
{

    /**
     * @Flow\InjectConfiguration(path="supportedDatabaseDrivers")
     * @var array<string, string>
     */
    protected $supportedDatabaseDrivers;

    /**
     * Return an array with the available database drivers
     *
     * @return array<string,string>
     */
    public function getAvailableDrivers(): array
    {
        $availableDrivers = [];
        foreach ($this->supportedDatabaseDrivers as $driver => $description) {
            if (extension_loaded($driver)) {
                $availableDrivers[$driver] = $description;
            }
        }
        return $availableDrivers;
    }

    /**
     * @param array $connectionSettings
     * @throws SetupException
     */
    public function verifyDatabaseConnectionWorks(array $connectionSettings)
    {
        try {
            $this->connectToDatabase($connectionSettings);
        } catch (\Exception $exception) {
            if (!$exception instanceof DBALException && !$exception instanceof \PDOException) {
                throw $exception;
            }
            try {
                $this->createDatabase($connectionSettings, $connectionSettings['dbname']);
            } catch (DBALException $exception) {
                throw new SetupException(sprintf('Database "%s" could not be created. Please check the permissions for user "%s". DBAL Exception: "%s"', $connectionSettings['dbname'], $connectionSettings['user'], $exception->getMessage()), 1351000841, $exception);
            } catch (\PDOException $exception) {
                throw new SetupException(sprintf('Database "%s" could not be created. Please check the permissions for user "%s". PDO Exception: "%s"', $connectionSettings['dbname'], $connectionSettings['user'], $exception->getMessage()), 1346758663, $exception);
            }
            try {
                $this->connectToDatabase($connectionSettings);
            } catch (DBALException $exception) {
                throw new SetupException(sprintf('Could not connect to database "%s". Please check the permissions for user "%s". DBAL Exception: "%s"', $connectionSettings['dbname'], $connectionSettings['user'], $exception->getMessage()), 1351000864);
            } catch (\PDOException $exception) {
                throw new SetupException(sprintf('Could not connect to database "%s". Please check the permissions for user "%s". PDO Exception: "%s"', $connectionSettings['dbname'], $connectionSettings['user'], $exception->getMessage()), 1346758737);
            }
        }
    }

    /**
     * Tries to connect to the database using the specified $connectionSettings
     *
     * @param array $connectionSettings array in the format array('user' => 'dbuser', 'password' => 'dbpassword', 'host' => 'dbhost', 'dbname' => 'dbname')
     * @return void
     * @throws \PDOException if the connection fails
     */
    protected function connectToDatabase(array $connectionSettings)
    {
        $connection = DriverManager::getConnection($connectionSettings);
        $connection->connect();
    }

    /**
     * Connects to the database using the specified $connectionSettings
     * and tries to create a database named $databaseName.
     *
     * @param array $connectionSettings array in the format array('user' => 'dbuser', 'password' => 'dbpassword', 'host' => 'dbhost', 'dbname' => 'dbname')
     * @param string $databaseName name of the database to create
     * @throws \Neos\Setup\Exception
     * @return void
     */
    protected function createDatabase(array $connectionSettings, $databaseName)
    {
        unset($connectionSettings['dbname']);
        $connection = DriverManager::getConnection($connectionSettings);
        $databasePlatform = $connection->getSchemaManager()->getDatabasePlatform();
        $databaseName = $databasePlatform->quoteIdentifier($databaseName);
        // we are not using $databasePlatform->getCreateDatabaseSQL() below since we want to specify charset and collation
        if ($databasePlatform instanceof MySqlPlatform) {
            $connection->executeUpdate(sprintf('CREATE DATABASE %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $databaseName));
        } elseif ($databasePlatform instanceof PostgreSqlPlatform) {
            $connection->executeUpdate(sprintf('CREATE DATABASE %s WITH ENCODING = %s', $databaseName, "'UTF8'"));
        } else {
            throw new SetupException(sprintf('The given database platform "%s" is not supported.', $databasePlatform->getName()), 1386454885);
        }
        $connection->close();
    }


}
