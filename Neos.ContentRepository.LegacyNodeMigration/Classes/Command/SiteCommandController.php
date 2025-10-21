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
use Neos\ContentRepository\LegacyNodeMigration\RootNodeTypeMapping;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Property\PropertyMapper;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Utility\Files;

class SiteCommandController extends CommandController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly PropertyMapper $propertyMapper,
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
    ) {
        parent::__construct();
    }

    /**
     * Migrate from the Legacy CR
     *
     * This command creates a Neos 9 export format based on the data from the specified legacy content repository database connection
     * The export will be placed in the specified directory path, and can be imported via "site:importAll":
     *
     *     ./flow site:exportLegacyData --path ./migratedContent
     *     ./flow site:importAll --path ./migratedContent
     *
     * Note that the dimension configuration and the node type schema must be migrated of the reference content repository
     *
     * @param string $contentRepository The reference content repository that can later be used for importing into
     * @param string $path The path to the directory to export to, will be created if missing
     * @param string|null $config JSON encoded configuration, for example --config '{"dbal": {"dbname": "some-other-db"}, "resourcesPath": "/absolute-path/Data/Persistent/Resources", "rootNodes": {"/sites": "Neos.Neos:Sites", "/other": "My.Package:SomeOtherRoot"}}'
     * @throws \Exception
     */
    public function exportLegacyDataCommand(string $path, string $contentRepository = 'default', ?string $config = null, bool $verbose = false): void
    {
        Files::createDirectoryRecursively($path);
        if ($config !== null) {
            try {
                $parsedConfig = json_decode($config, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \InvalidArgumentException(sprintf('Failed to parse --config parameter: %s', $e->getMessage()), 1659526855, $e);
            }
            $resourcesPath = $parsedConfig['resourcesPath'] ?? self::defaultResourcesPath();
            $rootNodes = isset($parsedConfig['rootNodes']) ? RootNodeTypeMapping::fromArray($parsedConfig['rootNodes']) : $this->getDefaultRootNodes();
            try {
                $connection = isset($parsedConfig['dbal']) ? DriverManager::getConnection(array_merge($this->connection->getParams(), $parsedConfig['dbal']), new Configuration()) : $this->connection;
            } catch (DBALException $e) {
                throw new \InvalidArgumentException(sprintf('Failed to get database connection, check the --config parameter: %s', $e->getMessage()), 1659527201, $e);
            }
        } else {
            $resourcesPath = $this->determineResourcesPath();
            $rootNodes = $this->getDefaultRootNodes();
            if (!$this->output->askConfirmation(sprintf('Do you want to migrate nodes from the current database "%s@%s" (y/n)? ', $this->connection->getParams()['dbname'] ?? '?', $this->connection->getParams()['host'] ?? '?'))) {
                $connection = $this->adjustDatabaseConnection($this->connection);
            } else {
                $connection = $this->connection;
            }
        }
        $this->verifyDatabaseConnection($connection);

        $legacyExportService = $this->contentRepositoryRegistry->buildService(
            ContentRepositoryId::fromString($contentRepository),
            new LegacyExportServiceFactory(
                $connection,
                $resourcesPath,
                $this->propertyMapper,
                $rootNodes,
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
    private function adjustDatabaseConnection(Connection $connection): Connection
    {
        $connectionParams = $connection->getParams();
        $connectionParams['driver'] = $this->output->select(sprintf('Driver? [%s] ', $connectionParams['driver'] ?? ''), ['pdo_mysql', 'pdo_sqlite', 'pdo_pgsql'], $connectionParams['driver'] ?? null);
        $connectionParams['host'] = $this->output->ask(sprintf('Host? [%s] ', $connectionParams['host'] ?? ''), $connectionParams['host'] ?? null);
        $port = $this->output->ask(sprintf('Port? [%s] ', $connectionParams['port'] ?? ''), isset($connectionParams['port']) ? (string)$connectionParams['port'] : null);
        $connectionParams['port'] = isset($port) ? (int)$port : null;
        $connectionParams['dbname'] = $this->output->ask(sprintf('DB name? [%s] ', $connectionParams['dbname'] ?? ''), $connectionParams['dbname'] ?? null);
        $connectionParams['user'] = $this->output->ask(sprintf('DB user? [%s] ', $connectionParams['user'] ?? ''), $connectionParams['user'] ?? null);
        /** @phpstan-ignore-next-line */
        $connectionParams['password'] = $this->output->askHiddenResponse(sprintf('DB password? [%s]', str_repeat('*', strlen($connectionParams['password'] ?? '')))) ?? $connectionParams['password'];
        /** @phpstan-ignore-next-line */
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
                $this->adjustDatabaseConnection($connection);
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
                Severity::WARNING => sprintf('<comment>Warning: %s</comment>', $message),
                Severity::ERROR => sprintf('<error>Error: %s</error>', $message),
            });
        };
    }

    private function getDefaultRootNodes(): RootNodeTypeMapping
    {
        return RootNodeTypeMapping::fromArray(['/sites' => NodeTypeNameFactory::NAME_SITES]);
    }
}
