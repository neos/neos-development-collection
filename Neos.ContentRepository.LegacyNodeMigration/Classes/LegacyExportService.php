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
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Export\Asset\Adapters\DbalAssetLoader;
use Neos\ContentRepository\Export\Asset\Adapters\FileSystemResourceLoader;
use Neos\ContentRepository\Export\Asset\AssetExporter;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\Processors;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepository\LegacyNodeMigration\Helpers\DomainDataLoader;
use Neos\ContentRepository\LegacyNodeMigration\Helpers\NodeDataLoader;
use Neos\ContentRepository\LegacyNodeMigration\Helpers\SiteDataLoader;
use Neos\ContentRepository\LegacyNodeMigration\Processors\AssetExportProcessor;
use Neos\ContentRepository\LegacyNodeMigration\Processors\EventExportProcessor;
use Neos\ContentRepository\LegacyNodeMigration\Processors\SitesExportProcessor;
use Neos\Flow\Property\PropertyMapper;

class LegacyExportService implements ContentRepositoryServiceInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $resourcesPath,
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly PropertyMapper $propertyMapper,
        private readonly EventNormalizer $eventNormalizer,
        private readonly PropertyConverter $propertyConverter,
        private readonly RootNodeTypeMapping $rootNodeTypeMapping,
    ) {
    }

    /**
     * @param \Closure(string): void $onProcessor Callback that is invoked for each {@see ProcessorInterface} that is processed
     * @param \Closure(Severity, string): void $onMessage Callback that is invoked whenever a {@see ProcessorInterface} dispatches a message
     */
    public function exportToPath(string $path, \Closure $onProcessor, \Closure $onMessage): void
    {
        $filesystem = new Filesystem(new LocalFilesystemAdapter($path));
        $assetExporter = new AssetExporter($filesystem, new DbalAssetLoader($this->connection), new FileSystemResourceLoader($this->resourcesPath));

        $processors = Processors::fromArray([
            'Exporting assets' => new AssetExportProcessor($this->nodeTypeManager, $assetExporter, new NodeDataLoader($this->connection)),
            'Exporting node data' => new EventExportProcessor($this->nodeTypeManager, $this->propertyMapper, $this->propertyConverter, $this->interDimensionalVariationGraph, $this->eventNormalizer, $this->rootNodeTypeMapping, new NodeDataLoader($this->connection)),
            'Exporting sites data' => new SitesExportProcessor(new SiteDataLoader($this->connection), new DomainDataLoader($this->connection)),
        ]);

        $processingContext = new ProcessingContext($filesystem, $onMessage);
        foreach ($processors as $processorLabel => $processor) {
            ($onProcessor)($processorLabel);
            $processor->run($processingContext);
        }
    }
}
