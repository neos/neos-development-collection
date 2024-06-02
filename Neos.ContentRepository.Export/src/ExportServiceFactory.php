<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Export;

use League\Flysystem\Filesystem;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\AssetUsage\Projection\AssetUsageFinder;

/**
 * @internal
 * @implements ContentRepositoryServiceFactoryInterface<ExportService>
 */
class ExportServiceFactory implements ContentRepositoryServiceFactoryInterface
{

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly ContentStreamId $targetContentStreamId,
        private readonly AssetRepository $assetRepository,
        private readonly AssetUsageFinder $assetUsageFinder,
    ) {
    }

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ExportService
    {
        return new ExportService(
            $this->filesystem,
            $this->targetContentStreamId,
            $this->assetRepository,
            $this->assetUsageFinder,
            $serviceFactoryDependencies->eventStore,
        );
    }
}
