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
use Doctrine\DBAL\Exception as DbalException;
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;
use Neos\ContentRepository\LegacyNodeMigration\LegacyMigrationService;
use Neos\ContentRepository\LegacyNodeMigration\LegacyMigrationServiceFactory;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Factory\EventStore\DoctrineEventStoreFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Core\Booting\Scripts;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Flow\Utility\Environment;
use Neos\Media\Domain\Repository\AssetRepository;

class ContentRepositoryMigrateCommandController extends CommandController
{

    /**
     * @var array
     */
    #[Flow\InjectConfiguration(package: 'Neos.Flow')]
    protected array $flowSettings;

    public function __construct(
        private readonly Connection $connection,
        private readonly Environment $environment,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly AssetRepository $assetRepository,
        private readonly ResourceRepository $resourceRepository,
        private readonly ResourceManager $resourceManager,
        private readonly PropertyMapper $propertyMapper,
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
    ) {
        parent::__construct();
    }

    /**
     * Migrate from the Legacy CR
     *
     * @param bool $verbose If set, all notices will be rendered
     * @param string|null $config JSON encoded configuration, for example '{"dbal": {"dbname": "some-other-db"}, "resourcesPath": "/some/absolute/path"}'
     * @throws \Exception
     */
    public function runCommand(bool $verbose = false, string $config = null, string $contentRepositoryIdentifier = 'default'): void
    {
        $contentRepositoryIdentifier = ContentRepositoryIdentifier::fromString($contentRepositoryIdentifier);
        if ($config !== null) {
            try {
                $parsedConfig = json_decode($config, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \InvalidArgumentException(sprintf('Failed to parse --config parameter: %s', $e->getMessage()), 1659526855, $e);
            }
            try {
                $connection = isset($parsedConfig['dbal']) ? DriverManager::getConnection(array_merge($this->connection->getParams(), $parsedConfig['dbal']), new Configuration()) : $this->connection;
            } catch (DbalException $e) {
                throw new \InvalidArgumentException(sprintf('Failed to get database connection, check the --config parameter: %s', $e->getMessage()), 1659527201, $e);
            }
            $resourcesPath = $parsedConfig['resourcesPath'] ?? self::defaultResourcesPath();
        } else {
            $connection = $this->determineConnection();
            $resourcesPath = $this->determineResourcesPath();
        }

        $eventTableName = DoctrineEventStoreFactory::databaseTableName($contentRepositoryIdentifier);
        $confirmed = $this->output->askConfirmation(sprintf('We will clear the events from "%s". ARE YOU SURE (y/n)? ', $eventTableName));
        if (!$confirmed) {
            $this->outputLine('Exiting');
            return;
        }
        $this->connection->executeStatement('TRUNCATE ' . $eventTableName);
        $this->outputLine('Truncated events');

        $legacyMigrationService = $this->contentRepositoryRegistry->getService(
            $contentRepositoryIdentifier,
            new LegacyMigrationServiceFactory(
                $connection,
                $resourcesPath,
                $this->environment,
                $this->persistenceManager,
                $this->assetRepository,
                $this->resourceRepository,
                $this->resourceManager,
                $this->propertyMapper
            )
        );
        assert($legacyMigrationService instanceof LegacyMigrationService);
        $legacyMigrationService->runAllProcessors($this->outputLine(...), $verbose);


        $this->outputLine();

        // TODO: 'assetUsage'
        $projections = ['graph', 'nodeHiddenState', 'documentUriPath', 'change', 'workspace', 'contentStream'];
        $this->outputLine('Replaying projections');
        $verbose && $this->output->progressStart(count($projections));
        foreach ($projections as $projection) {
            Scripts::executeCommand('neos.contentrepositoryregistry:cr:replay', $this->flowSettings, false, ['projectionName' => $projection]);
            /** @noinspection DisconnectedForeachInstructionInspection */
            $verbose && $this->output->progressAdvance();
        }
        $verbose && $this->output->progressFinish();

        $this->outputLine('<success>Done</success>');
    }

    /**
     * @return Connection
     * @throws DbalException
     */
    private function determineConnection(): Connection
    {
        $connectionParams = $this->connection->getParams();
        $useDefault = $this->output->askConfirmation(sprintf('Do you want to migrate nodes from the current database "%s@%s" (y/n)? ', $connectionParams['dbname'] ?? '?', $connectionParams['host'] ?? '?'));
        if ($useDefault) {
            return $this->connection;
        }
        $connectionParams['driver'] = $this->output->select(sprintf('Driver? [%s] ', $connectionParams['driver'] ?? ''), ['pdo_mysql', 'pdo_sqlite', 'pdo_pgsql'], $connectionParams['driver'] ?? null);
        $connectionParams['host'] = $this->output->ask(sprintf('Host? [%s] ',$connectionParams['host'] ?? ''), $connectionParams['host'] ?? null);
        $connectionParams['dbname'] = $this->output->ask(sprintf('DB name? [%s] ',$connectionParams['dbname'] ?? ''), $connectionParams['dbname'] ?? null);
        $connectionParams['user'] = $this->output->ask(sprintf('DB user? [%s] ',$connectionParams['user'] ?? ''), $connectionParams['user'] ?? null);
        $connectionParams['password'] = $this->output->askHiddenResponse(sprintf('DB password? [%s]', str_repeat('*', strlen($connectionParams['password'] ?? '')))) ?? $connectionParams['password'];
        return DriverManager::getConnection($connectionParams, new Configuration());
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
}
