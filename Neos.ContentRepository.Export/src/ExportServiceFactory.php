<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Export;

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
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;
use Neos\ESCR\AssetUsage\AssetUsageFinder;
use Neos\Media\Domain\Repository\AssetRepository;

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
            $serviceFactoryDependencies->eventStore,
            $this->assetRepository,
            $this->assetUsageFinder,
        );
    }
}
