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
use Neos\ContentRepositoryRegistry\Processors\ProjectionReplayProcessorFactory;
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
use Neos\Neos\Domain\Pruning\ContentRepositoryPruningProcessorFactory;
use Neos\Neos\Domain\Pruning\RoleAndMetadataPruningProcessorFactory;
use Neos\Neos\Domain\Pruning\SitePruningProcessorFactory;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;

#[Flow\Scope('singleton')]
final readonly class SitePruningService
{
    public function __construct(
        private ContentRepositoryRegistry        $contentRepositoryRegistry,
        private SiteRepository                   $siteRepository,
        private DomainRepository                 $domainRepository,
        private PersistenceManagerInterface      $persistenceManager,
        private ProjectionReplayProcessorFactory $projectionReplayServiceFactory,
        private WorkspaceService                 $workspaceService,
    ) {
    }

    /**
     * @param \Closure(string): void $onProcessor Callback that is invoked for each {@see ProcessorInterface} that is processed
     * @param \Closure(Severity, string): void $onMessage Callback that is invoked whenever a {@see ProcessorInterface} dispatches a message
     */
    public function pruneAll(ContentRepositoryId $contentRepositoryId, \Closure $onProcessor, \Closure $onMessage): void
    {
        $filesystem = new Filesystem(new LocalFilesystemAdapter('.'));
        $context = new ProcessingContext($filesystem, $onMessage);

        // TODO make configurable (?)
        /** @var array<string, ProcessorInterface> $processors */
        $processors = [
            'Remove site nodes' => $this->contentRepositoryRegistry->buildService(
                $contentRepositoryId,
                new SitePruningProcessorFactory(
                    WorkspaceName::forLive(),
                    $this->siteRepository,
                    $this->domainRepository,
                    $this->persistenceManager
                )
            ),
            'Prune content repository' => $this->contentRepositoryRegistry->buildService(
                $contentRepositoryId,
                new ContentRepositoryPruningProcessorFactory()
            ),
            'Prune roles and metadata' => $this->contentRepositoryRegistry->buildService(
                $contentRepositoryId,
                new RoleAndMetadataPruningProcessorFactory(
                    $this->workspaceService
                )
            ),
            'Replay all projections' => $this->contentRepositoryRegistry->buildService($contentRepositoryId, $this->projectionReplayServiceFactory),
        ];

        foreach ($processors as $processorLabel => $processor) {
            ($onProcessor)($processorLabel);
            $processor->run($context);
        }
    }
}
