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
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainerFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\Processors;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Pruning\ContentRepositoryPruningProcessor;
use Neos\Neos\Domain\Pruning\RoleAndMetadataPruningProcessor;
use Neos\Neos\Domain\Pruning\SitePruningProcessor;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Repository\WorkspaceMetadataAndRoleRepository;

#[Flow\Scope('singleton')]
final readonly class SitePruningService
{
    public function __construct(
        private ContentRepositoryRegistry $contentRepositoryRegistry,
        private SiteRepository $siteRepository,
        private DomainRepository $domainRepository,
        private PersistenceManagerInterface $persistenceManager,
        private WorkspaceMetadataAndRoleRepository $workspaceMetadataAndRoleRepository,
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

        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $processors = Processors::fromArray([
            'Remove site nodes' => new SitePruningProcessor(
                $contentRepository,
                WorkspaceName::forLive(),
                $this->siteRepository,
                $this->domainRepository,
                $this->persistenceManager
            ),
            'Prune roles and metadata' => new RoleAndMetadataPruningProcessor($contentRepositoryId, $this->workspaceMetadataAndRoleRepository),
            'Prune content repository' => new ContentRepositoryPruningProcessor(
                $this->contentRepositoryRegistry->buildService(
                    $contentRepositoryId,
                    new ContentRepositoryMaintainerFactory()
                )
            )
        ]);

        foreach ($processors as $processorLabel => $processor) {
            ($onProcessor)($processorLabel);
            $processor->run($context);
        }
    }
}
