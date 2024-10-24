<?php
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration\Command;

/*
 * This file is part of the Neos.ContentRepository.LegacyNodeMigration package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\ConnectionException;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepository\LegacyNodeMigration\LegacyExportServiceFactory;
use Neos\ContentRepository\LegacyNodeMigration\LegacyMigrationService;
use Neos\ContentRepository\LegacyNodeMigration\LegacyMigrationServiceFactory;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Utility\Environment;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\Utility\Files;

class SiteCommandController extends CommandController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Environment $environment,
        private readonly PropertyMapper $propertyMapper,
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly SiteImportService $siteImportService,
    ) {
        parent::__construct();
    }

    /**
     * Migrate from the Legacy CR
     *
     * @param string|null $config JSON encoded configuration, for example '{"dbal": {"dbname": "some-other-db"}, "resourcesPath": "/some/absolute/path"}'
     * @throws \Exception
     */
    public function migrateLegacyDataCommand(string $contentRepository = 'default', bool $verbose = false, string $config = null): void
    {
        if ($config !== null) {
            try {
                $parsedConfig = json_decode($config, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \InvalidArgumentException(sprintf('Failed to parse --config parameter: %s', $e->getMessage()), 1659526855, $e);
            }
            $resourcesPath = $parsedConfig['resourcesPath'] ?? self::defaultResourcesPath();
            try {
                $connection = isset($parsedConfig['dbal']) ? DriverManager::getConnection(array_merge($this->connection->getParams(), $parsedConfig['dbal']), new Configuration()) : $this->connection;
            } catch (DBALException $e) {
                throw new \InvalidArgumentException(sprintf('Failed to get database connection, check the --config parameter: %s', $e->getMessage()), 1659527201, $e);
            }
        } else {
            $resourcesPath = $this->determineResourcesPath();
            if (!$this->output->askConfirmation(sprintf('Do you want to migrate nodes from the current database "%s@%s" (y/n)? ', $this->connection->getParams()['dbname'] ?? '?', $this->connection->getParams()['host'] ?? '?'))) {
                $connection = $this->adjustDataBaseConnection($this->connection);
            } else {
                $connection = $this->connection;
            }
        }
        $this->verifyDatabaseConnection($connection);

        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $temporaryFilePath = $this->environment->getPathToTemporaryDirectory() . uniqid('Export', true);
        Files::createDirectoryRecursively($temporaryFilePath);

        $legacyExportService = $this->contentRepositoryRegistry->buildService(
            $contentRepositoryId,
            new LegacyExportServiceFactory(
                $connection,
                $resourcesPath,
                $this->propertyMapper,
            )
        );

        $legacyExportService->exportToPath(
            $temporaryFilePath,
            $this->createOnProcessorClosure(),
            $this->createOnMessageClosure($verbose)
        );

        $this->siteImportService->importFromPath(
            $contentRepositoryId,
            $temporaryFilePath,
            $this->createOnProcessorClosure(),
            $this->createOnMessageClosure($verbose)
        );

        Files::unlink($temporaryFilePath);

        $this->outputLine('<success>Done</success>');
    }

    /**
     * Export from the Legacy CR into a specified directory path
     *
     * @param string $path The path to the directory, will be created if missing
     * @param string|null $config JSON encoded configuration, for example '{"dbal": {"dbname": "some-other-db"}, "resourcesPath": "/some/absolute/path"}'
     * @throws \Exception
     */
    public function exportLegacyDataCommand(string $path, bool $verbose = false, string $config = null): void
    {
        if ($config !== null) {
            try {
                $parsedConfig = json_decode($config, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \InvalidArgumentException(sprintf('Failed to parse --config parameter: %s', $e->getMessage()), 1659526855, $e);
            }
            $resourcesPath = $parsedConfig['resourcesPath'] ?? self::defaultResourcesPath();
            try {
                $connection = isset($parsedConfig['dbal']) ? DriverManager::getConnection(array_merge($this->connection->getParams(), $parsedConfig['dbal']), new Configuration()) : $this->connection;
            } catch (DBALException $e) {
                throw new \InvalidArgumentException(sprintf('Failed to get database connection, check the --config parameter: %s', $e->getMessage()), 1659527201, $e);
            }
        } else {
            $resourcesPath = $this->determineResourcesPath();
            if (!$this->output->askConfirmation(sprintf('Do you want to migrate nodes from the current database "%s@%s" (y/n)? ', $this->connection->getParams()['dbname'] ?? '?', $this->connection->getParams()['host'] ?? '?'))) {
                $connection = $this->adjustDataBaseConnection($this->connection);
            } else {
                $connection = $this->connection;
            }
        }
        $this->verifyDatabaseConnection($connection);

        Files::createDirectoryRecursively($path);
        $legacyExportService = $this->contentRepositoryRegistry->buildService(
            ContentRepositoryId::fromString('default'),
            new LegacyExportServiceFactory(
                $connection,
                $resourcesPath,
                $this->propertyMapper,
            )
        );

        $legacyExportService->exportToPath(
            $path,
            $this->createOnProcessorClosure(),
            $this->createOnMessageClosure($verbose)
        );

        $this->outputLine('<success>Done</success>');
    }

    /**
     * @throws DBALException
     */
    private function adjustDataBaseConnection(Connection $connection): Connection
    {
        $connectionParams = $connection->getParams();
        $connectionParams['driver'] = $this->output->select(sprintf('Driver? [%s] ', $connectionParams['driver'] ?? ''), ['pdo_mysql', 'pdo_sqlite', 'pdo_pgsql'], $connectionParams['driver'] ?? null);
        $connectionParams['host'] = $this->output->ask(sprintf('Host? [%s] ',$connectionParams['host'] ?? ''), $connectionParams['host'] ?? null);
        $port = $this->output->ask(sprintf('Port? [%s] ',$connectionParams['port'] ?? ''), isset($connectionParams['port']) ? (string)$connectionParams['port'] : null);
        $connectionParams['port'] = isset($port) ? (int)$port : null;
        $connectionParams['dbname'] = $this->output->ask(sprintf('DB name? [%s] ',$connectionParams['dbname'] ?? ''), $connectionParams['dbname'] ?? null);
        $connectionParams['user'] = $this->output->ask(sprintf('DB user? [%s] ',$connectionParams['user'] ?? ''), $connectionParams['user'] ?? null);
        /** @phpstan-ignore-next-line */
        $connectionParams['password'] = $this->output->askHiddenResponse(sprintf('DB password? [%s]', str_repeat('*', strlen($connectionParams['password'] ?? '')))) ?? $connectionParams['password'];
        /** @phpstan-ignore-next-line  */
        return DriverManager::getConnection($connectionParams, new Configuration());
    }

    private function verifyDatabaseConnection(Connection $connection): void
    {
        do {
            try {
                $connection->connect();
                $this->outputLine('<success>Successfully connected to database "%s"</success>', [$connection->getDatabase()]);
                break;
            } catch (ConnectionException $exception) {
                $this->outputLine('<error>Failed to connect to database "%s": %s</error>', [$connection->getDatabase(), $exception->getMessage()]);
                $this->outputLine('Please verify connection parameters...');
                $this->adjustDataBaseConnection($connection);
            }
        } while (true);
    }

    private function determineResourcesPath(): string
    {
        $defaultResourcesPath = self::defaultResourcesPath();
        $useDefault = $this->output->askConfirmation(sprintf('Do you want to migrate resources from the current installation "%s" (y/n)? ', $defaultResourcesPath));
        if ($useDefault) {
            return $defaultResourcesPath;
        }
        $path = $this->output->ask('Absolute path to persistent resources (usually "<project>/Data/Persistent/Resources") ? ');
        if (!is_dir($path) || !is_readable($path)) {
            throw new \InvalidArgumentException(sprintf('Path "%s" is not a readable directory', $path), 1658736039);
        }
        return $path;
    }

    private static function defaultResourcesPath(): string
    {
        return FLOW_PATH_DATA . 'Persistent/Resources';
    }

    protected function createOnProcessorClosure(): \Closure
    {
        $onProcessor = function (string $processorLabel) {
            $this->outputLine('<info>%s...</info>', [$processorLabel]);
        };
        return $onProcessor;
    }

    protected function createOnMessageClosure(bool $verbose): \Closure
    {
        return function (Severity $severity, string $message) use ($verbose) {
            if (!$verbose && $severity === Severity::NOTICE) {
                return;
            }
            $this->outputLine(match ($severity) {
                Severity::NOTICE => $message,
                Severity::WARNING => sprintf('<error>Warning: %s</error>', $message),
                Severity::ERROR => sprintf('<error>Error: %s</error>', $message),
            });
        };
    }
}
