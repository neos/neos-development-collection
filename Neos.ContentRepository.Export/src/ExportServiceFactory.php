<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Export;

use League\Flysystem\Filesystem;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;
use Neos\Neos\AssetUsage\Projection\AssetUsageFinder;
use Neos\Media\Domain\Repository\AssetRepository;

/**
 * @internal
 */
class ExportServiceFactory implements ContentRepositoryServiceFactoryInterface
{

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly WorkspaceFinder $workspaceFinder,
        private readonly AssetRepository $assetRepository,
        private readonly AssetUsageFinder $assetUsageFinder,
    ) {
    }

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ExportService
    {
        return new ExportService(
            $this->filesystem,
            $this->workspaceFinder,
            $this->assetRepository,
            $this->assetUsageFinder,
            $serviceFactoryDependencies->eventStore,
        );
    }
}
