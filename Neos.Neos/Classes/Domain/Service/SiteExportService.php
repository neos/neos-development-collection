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
use Neos\ContentRepository\Export\Factory\EventExportProcessorFactory;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\Processors;
use Neos\ContentRepository\Export\Processors\AssetExportProcessor;
use Neos\ContentRepository\Export\Severity;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\AssetUsage\AssetUsageService;
use Neos\Neos\Domain\Export\SiteExportProcessor;
use Neos\Neos\Domain\Repository\SiteRepository;

#[Flow\Scope('singleton')]
final readonly class SiteExportService
{
    public function __construct(
        private ContentRepositoryRegistry $contentRepositoryRegistry,
        private AssetRepository $assetRepository,
        private AssetUsageService $assetUsageService,
        private SiteRepository $siteRepository,
    ) {
    }

    /**
     * @param \Closure(string): void $onProcessor Callback that is invoked for each {@see ProcessorInterface} that is processed
     * @param \Closure(Severity, string): void $onMessage Callback that is invoked whenever a {@see ProcessorInterface} dispatches a message
     */
    public function exportToPath(ContentRepositoryId $contentRepositoryId, string $path, \Closure $onProcessor, \Closure $onMessage): void
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException(sprintf('Path "%s" is not a directory', $path), 1729593802);
        }
        $filesystem = new Filesystem(new LocalFilesystemAdapter($path));
        $context = new ProcessingContext($filesystem, $onMessage);
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $liveWorkspace = $contentRepository->findWorkspaceByName(WorkspaceName::forLive());
        if ($liveWorkspace === null) {
            throw new \RuntimeException('Failed to find live workspace', 1716652280);
        }

        $processors = Processors::fromArray([
            'Exporting events' => $this->contentRepositoryRegistry->buildService(
                $contentRepositoryId,
                new EventExportProcessorFactory(
                    $liveWorkspace->currentContentStreamId
                )
            ),
            'Exporting assets' => new AssetExportProcessor(
                $contentRepositoryId,
                $this->assetRepository,
                $liveWorkspace,
                $this->assetUsageService
            ),
            'Export sites' => new SiteExportProcessor(
                $contentRepository,
                $liveWorkspace->workspaceName,
                $this->siteRepository
            ),
        ]);

        foreach ($processors as $processorLabel => $processor) {
            ($onProcessor)($processorLabel);
            $processor->run($context);
        }
    }
}
