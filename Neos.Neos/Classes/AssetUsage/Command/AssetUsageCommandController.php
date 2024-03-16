<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Command;

use Neos\Flow\Cli\CommandController;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Neos\AssetUsage\Projection\AssetUsageRepositoryFactory;
use Neos\Neos\AssetUsage\Service\AssetUsageSyncServiceFactory;

final class AssetUsageCommandController extends CommandController
{
    public function __construct(
        private readonly AssetRepository $assetRepository,
        private readonly AssetUsageRepositoryFactory $assetUsageRepositoryFactory,
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
    ) {
        parent::__construct();
    }

    /**
     * Remove asset usages that are no longer valid
     *
     * This is the case for usages that refer to
     * * deleted nodes (i.e. nodes that were implicitly removed because an ancestor node was deleted)
     * * invalid dimension space points (e.g. because dimension configuration has been changed)
     * * removed content streams
     *
     * @param bool $quiet if Set, only errors will be outputted
     */
    public function syncCommand(string $contentRepository = 'default', bool $quiet = false): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $assetUsageSyncService = $this->contentRepositoryRegistry->buildService(
            $contentRepositoryId,
            new AssetUsageSyncServiceFactory(
                $this->assetRepository,
                $this->assetUsageRepositoryFactory
            )
        );

        $usages = $assetUsageSyncService->findAllUsages();
        if (!$quiet) {
            $this->output->progressStart($usages->count());
        }
        $numberOfRemovedUsages = 0;
        foreach ($usages as $usage) {
            if (!$assetUsageSyncService->isAssetUsageStillValid($usage)) {
                $assetUsageSyncService->removeAssetUsage($usage);
                $numberOfRemovedUsages++;
            }
            if (!$quiet) {
                $this->output->progressAdvance();
            }
        }
        if (!$quiet) {
            $this->output->progressFinish();
            $this->outputLine();
            $this->outputLine('Removed %d asset usage%s', [
                $numberOfRemovedUsages, $numberOfRemovedUsages === 1 ? '' : 's'
            ]);
        }
    }
}
