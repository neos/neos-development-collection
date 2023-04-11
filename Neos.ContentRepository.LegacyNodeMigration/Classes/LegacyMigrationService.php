<?php
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration;

/*
 * This file is part of the Neos.ContentRepository.LegacyNodeMigration package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


use Doctrine\DBAL\Connection;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Export\Asset\Adapters\DbalAssetLoader;
use Neos\ContentRepository\Export\Asset\Adapters\FileSystemResourceLoader;
use Neos\ContentRepository\Export\Asset\AssetExporter;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\Processors\AssetRepositoryImportProcessor;
use Neos\ContentRepository\Export\Processors\EventStoreImportProcessor;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\LegacyNodeMigration\Helpers\NodeDataLoader;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\EventStoreInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Flow\Utility\Environment;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Utility\Files;

class LegacyMigrationService implements ContentRepositoryServiceInterface
{

    public function __construct(
        private readonly Connection $connection,
        private readonly string $resourcesPath,
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
        private readonly EventStoreInterface $eventStore,
        private readonly ContentStreamId $contentStreamId,
    )
    {
    }

    public function runAllProcessors(\Closure $outputLineFn, bool $verbose = false): void
    {

        $temporaryFilePath = $this->environment->getPathToTemporaryDirectory() . uniqid('Export', true);
        Files::createDirectoryRecursively($temporaryFilePath);
        $filesystem = new Filesystem(new LocalFilesystemAdapter($temporaryFilePath));

        $assetExporter = new AssetExporter($filesystem, new DbalAssetLoader($this->connection), new FileSystemResourceLoader($this->resourcesPath));

        /** @var ProcessorInterface[] $processors */
        $processors = [
            'Exporting assets' => new NodeDataToAssetsProcessor($this->nodeTypeManager, $assetExporter, new NodeDataLoader($this->connection)),
            'Exporting node data' => new NodeDataToEventsProcessor($this->nodeTypeManager, $this->propertyMapper, $this->propertyConverter, $this->interDimensionalVariationGraph, $this->eventNormalizer, $filesystem, new NodeDataLoader($this->connection)),
            'Importing assets' => new AssetRepositoryImportProcessor($filesystem, $this->assetRepository, $this->resourceRepository, $this->resourceManager, $this->persistenceManager),
            'Importing events' => new EventStoreImportProcessor(true, $filesystem, $this->eventStore, $this->eventNormalizer, $this->contentStreamId),
        ];

        foreach ($processors as $label => $processor) {
            $outputLineFn($label . '...');
            $verbose && $processor->onMessage(fn(Severity $severity, string $message) => $outputLineFn('<%1$s>%2$s</%1$s>', [$severity === Severity::ERROR ? 'error' : 'comment', $message]));
            $result = $processor->run();
            if ($result->severity === Severity::ERROR) {
                throw new \RuntimeException($label . ': ' . $result->message ?? '');
            }
            $outputLineFn('  ' . $result->message);
            $outputLineFn();
        }
        Files::unlink($temporaryFilePath);
    }
}
