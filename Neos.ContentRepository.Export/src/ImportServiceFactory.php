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
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Repository\AssetRepository;

class ImportServiceFactory implements ContentRepositoryServiceFactoryInterface
{

    public function __construct(
        private readonly Filesystem      $filesystem,
        private readonly ContentStreamId $contentStreamIdentifier,
        private readonly AssetRepository $assetRepository,
        private readonly ResourceRepository $resourceRepository,
        private readonly ResourceManager $resourceManager,
        private readonly PersistenceManagerInterface $persistenceManager,
    )
    {
    }

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ImportService
    {
        return new ImportService(
            $this->filesystem,
            $this->contentStreamIdentifier,
            $this->assetRepository,
            $this->resourceRepository,
            $this->resourceManager,
            $this->persistenceManager,
            $serviceFactoryDependencies->eventNormalizer,
            $serviceFactoryDependencies->eventStore,
        );
    }
}
