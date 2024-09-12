<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Export;

use League\Flysystem\Filesystem;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\AssetUsage\AssetUsageService;

/**
 * @internal
 * @implements ContentRepositoryServiceFactoryInterface<ExportService>
 */
class ExportServiceFactory implements ContentRepositoryServiceFactoryInterface
{

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly WorkspaceFinder $workspaceFinder,
        private readonly AssetRepository $assetRepository,
        private readonly AssetUsageService $assetUsageService,
    ) {
    }

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ExportService
    {
        return new ExportService(
            $serviceFactoryDependencies->contentRepositoryId,
            $this->filesystem,
            $this->workspaceFinder,
            $this->assetRepository,
            $this->assetUsageService,
            $serviceFactoryDependencies->eventStore,
        );
    }
}
