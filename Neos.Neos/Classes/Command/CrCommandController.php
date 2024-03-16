<?php

declare(strict_types=1);

namespace Neos\Neos\Command;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Export\ExportService;
use Neos\ContentRepository\Export\ExportServiceFactory;
use Neos\ContentRepository\Export\ImportService;
use Neos\ContentRepository\Export\ImportServiceFactory;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Service\ProjectionReplayServiceFactory;
use Neos\Neos\AssetUsage\Projection\AssetUsageFinder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Utility\Files;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Persistence\PersistenceManagerInterface;

class CrCommandController extends CommandController
{
    /**
     * @var array<string|int,mixed>
     */
    #[Flow\InjectConfiguration(package: 'Neos.Flow')]
    protected array $flowSettings;

    public function __construct(
        private readonly AssetRepository $assetRepository,
        private readonly ResourceRepository $resourceRepository,
        private readonly ResourceManager $resourceManager,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly ProjectionReplayServiceFactory $projectionReplayServiceFactory,
    ) {
        parent::__construct();
    }

    /**
     * Export the events from the specified content repository
     *
     * @param string $path The path for storing the result
     * @param string $contentRepository The content repository identifier
     * @param bool $verbose If set, all notices will be rendered
     * @throws \Exception
     */
    public function exportCommand(string $path, string $contentRepository = 'default', bool $verbose = false): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        Files::createDirectoryRecursively($path);
        $filesystem = new Filesystem(new LocalFilesystemAdapter($path));

        $exportService = $this->contentRepositoryRegistry->buildService(
            $contentRepositoryId,
            new ExportServiceFactory(
                $filesystem,
                $contentRepository->getWorkspaceFinder(),
                $this->assetRepository,
                $contentRepository->projectionState(AssetUsageFinder::class),
            )
        );
        assert($exportService instanceof ExportService);
        $exportService->runAllProcessors($this->outputLine(...), $verbose);
        $this->outputLine('<success>Done</success>');
    }

    /**
     * Import the events from the path into the specified content repository
     *
     * @param string $path The path of the stored events like resource://Neos.Demo/Private/Content
     * @param string $contentRepository The content repository identifier
     * @param bool $verbose If set, all notices will be rendered
     * @throws \Exception
     */
    public function importCommand(string $path, string $contentRepository = 'default', bool $verbose = false): void
    {
        $filesystem = new Filesystem(new LocalFilesystemAdapter($path));

        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $contentStreamIdentifier = ContentStreamId::create();

        $importService = $this->contentRepositoryRegistry->buildService(
            $contentRepositoryId,
            new ImportServiceFactory(
                $filesystem,
                $contentStreamIdentifier,
                $this->assetRepository,
                $this->resourceRepository,
                $this->resourceManager,
                $this->persistenceManager,
            )
        );
        assert($importService instanceof ImportService);
        try {
            $importService->runAllProcessors($this->outputLine(...), $verbose);
        } catch (\RuntimeException $exception) {
            $this->outputLine('<error>Error: ' . $exception->getMessage() . '</error>');
            $this->outputLine('<error>Import stopped.</error>');
            return;
        }

        $this->outputLine('Replaying projections');

        $projectionService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, $this->projectionReplayServiceFactory);
        $projectionService->replayAllProjections(CatchUpOptions::create());

        $this->outputLine('<success>Done</success>');
    }
}
