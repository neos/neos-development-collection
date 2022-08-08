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
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Export\Asset\Adapters\DbalAssetLoader;
use Neos\ContentRepository\Export\Asset\Adapters\FileSystemResourceLoader;
use Neos\ContentRepository\Export\Asset\AssetExporter;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\Processors\AssetRepositoryImportProcessor;
use Neos\ContentRepository\Export\Processors\EventStoreImportProcessor;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\LegacyNodeMigration\Helpers\NodeDataLoader;
use Neos\ContentRepository\LegacyNodeMigration\NodeDataToAssetsProcessor;
use Neos\ContentRepository\LegacyNodeMigration\NodeDataToEventsProcessor;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\EventStore\EventStoreFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Core\Booting\Scripts;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Flow\Utility\Environment;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Utility\Files;

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
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly PropertyMapper $propertyMapper,
        private readonly EventNormalizer $eventNormalizer,
        private readonly PropertyConverter $propertyConverter,
        private readonly EventStoreFactory $eventStoreFactory,
    ) {
        parent::__construct();
    }

    /**
     * Run a CR export
     *
     * @param bool $verbose If set, all notices will be rendered
     * @param string|null $config JSON encoded configuration, for example '{"dbal": {"dbname": "some-other-db"}, "resourcesPath": "/some/absolute/path"}'
     * @throws \Exception
     */
    public function runCommand(bool $verbose = false, string $config = null): void
    {
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
        $temporaryFilePath = $this->environment->getPathToTemporaryDirectory() . uniqid('Export', true);
        Files::createDirectoryRecursively($temporaryFilePath);
        $filesystem = new Filesystem(new LocalFilesystemAdapter($temporaryFilePath));

        $assetExporter = new AssetExporter($filesystem, new DbalAssetLoader($connection), new FileSystemResourceLoader($resourcesPath));
        $eventStore = $this->eventStoreFactory->create('ContentRepository');

        /** @var ProcessorInterface[] $processors */
        $processors = [
            'Exporting assets' => new NodeDataToAssetsProcessor($this->nodeTypeManager, $assetExporter, new NodeDataLoader($connection)),
            'Exporting node data' => new NodeDataToEventsProcessor($this->nodeTypeManager, $this->propertyMapper, $this->propertyConverter, $this->interDimensionalVariationGraph, $this->eventNormalizer, $filesystem, new NodeDataLoader($connection)),
            'Importing assets' => new AssetRepositoryImportProcessor($filesystem, $this->assetRepository, $this->resourceRepository, $this->resourceManager, $this->persistenceManager),
            'Importing events' => new EventStoreImportProcessor(true, true, $filesystem, $eventStore, $this->eventNormalizer),
        ];


        foreach ($processors as $label => $processor) {
            $this->outputLine($label . '...');
            $verbose && $processor->onMessage(fn (Severity $severity, string $message) => $this->outputLine('<%1$s>%2$s</%1$s>', [$severity === Severity::ERROR ? 'error' : 'comment', $message]));
            $result = $processor->run();
            if ($result->severity === Severity::ERROR) {
                throw new \RuntimeException($result->message);
            }
            $this->outputLine('  ' . $result->message);
            $this->outputLine();
        }

        $this->outputLine();

        $projections = ['graph', 'nodeHiddenState', 'documentUriPath', 'change', 'workspace', 'assetUsage', 'contentStream'];
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
