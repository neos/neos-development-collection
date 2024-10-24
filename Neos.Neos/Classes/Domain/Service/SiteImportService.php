<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Service;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Export\Factory\ContentRepositorySetupProcessorFactory;
use Neos\ContentRepository\Export\Factory\EventStoreImportProcessorFactory;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\Processors\AssetRepositoryImportProcessor;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Processors\ProjectionCatchupProcessor;
use Neos\ContentRepositoryRegistry\Processors\ProjectionCatchupProcessorFactory;
use Neos\ContentRepositoryRegistry\Service\ProjectionServiceFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\Service as DoctrineService;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Domain\Import\DoctrineMigrateProcessor;
use Neos\Neos\Domain\Import\LiveWorkspaceCreationProcessor;
use Neos\Neos\Domain\Import\LiveWorkspaceIsEmptyProcessorFactory;
use Neos\Neos\Domain\Import\SiteCreationProcessor;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;

#[Flow\Scope('singleton')]
final readonly class SiteImportService
{
    public function __construct(
        private ContentRepositoryRegistry $contentRepositoryRegistry,
        private DoctrineService $doctrineService,
        private SiteRepository $siteRepository,
        private DomainRepository $domainRepository,
        private AssetRepository $assetRepository,
        private ResourceRepository $resourceRepository,
        private ResourceManager $resourceManager,
        private PersistenceManagerInterface $persistenceManager,
        private WorkspaceService $workspaceService,
        private ProjectionCatchupProcessorFactory $projectionCatchupProcessorFactory,
    ) {
    }

    /**
     * @param \Closure(string): void $onProcessor Callback that is invoked for each {@see ProcessorInterface} that is processed
     * @param \Closure(Severity, string): void $onMessage Callback that is invoked whenever a {@see ProcessorInterface} dispatches a message
     */
    public function importFromPath(ContentRepositoryId $contentRepositoryId, string $path, \Closure $onProcessor, \Closure $onMessage): void
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException(sprintf('Path "%s" is not a directory', $path), 1729593802);
        }
        $filesystem = new Filesystem(new LocalFilesystemAdapter($path));
        $context = new ProcessingContext($filesystem, $onMessage);
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        // TODO make configurable (?)
        /** @var array<string, ProcessorInterface> $processors */
        $processors = [
            'Run doctrine migrations' => new DoctrineMigrateProcessor($this->doctrineService),
            'Setup content repository' => $this->contentRepositoryRegistry->buildService($contentRepositoryId, new ContentRepositorySetupProcessorFactory()),
            'Verify Live workspace does not exist yet' => $this->contentRepositoryRegistry->buildService($contentRepositoryId, new LiveWorkspaceIsEmptyProcessorFactory()),
            'Create Neos sites' => new SiteCreationProcessor($this->siteRepository, $this->domainRepository, $this->persistenceManager),
            'Create Live workspace' => new LiveWorkspaceCreationProcessor($contentRepository, $this->workspaceService),
            'Import events' => $this->contentRepositoryRegistry->buildService($contentRepositoryId, new EventStoreImportProcessorFactory(WorkspaceName::forLive(), keepEventIds: true)),
            'Import assets' => new AssetRepositoryImportProcessor($this->assetRepository, $this->resourceRepository, $this->resourceManager, $this->persistenceManager),
            'Catchup all projections' => $this->contentRepositoryRegistry->buildService($contentRepositoryId, $this->projectionCatchupProcessorFactory),
        ];

        foreach ($processors as $processorLabel => $processor) {
            ($onProcessor)($processorLabel);
            $processor->run($context);
        }
    }
}
