<?php

declare(strict_types=1);

namespace Neos\Neos\Command;

/*
 * This file is part of the Neos.ContentRepository.LegacyNodeMigration package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Service\ContentRepositoryBootstrapper;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Export\ExportService;
use Neos\ContentRepository\Export\ExportServiceFactory;
use Neos\ContentRepository\Export\ImportService;
use Neos\ContentRepository\Export\ImportServiceFactory;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ESCR\AssetUsage\AssetUsageFinder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Core\Booting\Scripts;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Utility\Files;

class CrCommandController extends CommandController
{
    /**
     * @var array
     */
    #[Flow\InjectConfiguration(package: 'Neos.Flow')]
    protected array $flowSettings;

    public function __construct(
        private readonly AssetRepository $assetRepository,
        private readonly AssetUsageFinder $assetUsageFinder,
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
    ) {
        parent::__construct();
    }

    /**
     * Export the events from the specified cr
     *
     * @param string $path The path for storing the result
     * @param string $cr The content repository identifier
     * @param bool $verbose If set, all notices will be rendered
     * @throws \Exception
     */
    public function exportCommand(string $path, string $cr = 'default', bool $verbose = false): void
    {
        $contentRepositoryIdentifier = ContentRepositoryId::fromString($cr);
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryIdentifier);
        $contentRepositoryBootstrapper = ContentRepositoryBootstrapper::create($contentRepository);
        $liveContentStreamIdentifier = $contentRepositoryBootstrapper->getOrCreateLiveContentStream();
        $contentRepositoryBootstrapper->getOrCreateRootNodeAggregate($liveContentStreamIdentifier, NodeTypeNameFactory::forSites());

        Files::createDirectoryRecursively($path);
        $filesystem = new Filesystem(new LocalFilesystemAdapter($path));

        $exportService = $this->contentRepositoryRegistry->getService(
            $contentRepositoryIdentifier,
            new ExportServiceFactory(
                $filesystem,
                $contentRepository->getWorkspaceFinder(),
                $this->assetRepository,
                $this->assetUsageFinder,
                $liveContentStreamIdentifier
            )
        );
        assert($exportService instanceof ExportService);
        $exportService->runAllProcessors($this->outputLine(...), $verbose);
        $this->outputLine('<success>Done</success>');
    }

    /**
     * Import the events from the path into the specified cr
     *
     * @param string $path The path for storing the result
     * @param string $cr The content repository identifier
     * @param bool $verbose If set, all notices will be rendered
     * @throws \Exception
     */
    public function importCommand(string $path, string $cr = 'default', bool $verbose = false,): void
    {
        $filesystem = new Filesystem(new LocalFilesystemAdapter($path));

        $contentRepositoryIdentifier = ContentRepositoryId::fromString($cr);
        $contentStreamIdentifier = ContentStreamId::create();

        $this->outputLine('Importing events');

        $importService = $this->contentRepositoryRegistry->getService(
            $contentRepositoryIdentifier,
            new ImportServiceFactory(
                $filesystem,
                $contentStreamIdentifier
            )
        );
        assert($importService instanceof ImportService);
        $importService->runAllProcessors($this->outputLine(...), $verbose);

        $this->outputLine('Replaying projections');
        Scripts::executeCommand('neos.contentrepositoryregistry:cr:replayall', $this->flowSettings, false, ['contentRepositoryIdentifier' => $contentRepositoryIdentifier]);

        $this->outputLine('<success>Done</success>');
    }
}
