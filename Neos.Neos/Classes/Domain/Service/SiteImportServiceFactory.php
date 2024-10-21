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

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Export\Processors;
use Neos\ContentRepository\Export\Processors\AssetRepositoryImportProcessor;
use Neos\ContentRepository\Export\Processors\ContentRepositorySetupProcessor;
use Neos\ContentRepository\Export\Processors\EventStoreImportProcessor;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Persistence\Doctrine\Service as DoctrineService;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Domain\Import\DoctrineMigrateProcessor;
use Neos\Neos\Domain\Import\SiteCreationProcessor;
use Neos\Neos\Domain\Repository\SiteRepository;

/**
 * @implements ContentRepositoryServiceFactoryInterface<SiteImportService>
 */
#[Flow\Scope('singleton')]
final readonly class SiteImportServiceFactory implements ContentRepositoryServiceFactoryInterface
{
    public function __construct(
        private PackageManager $packageManager,
        private DoctrineService $doctrineService,
        private SiteRepository $siteRepository,
        private AssetRepository $assetRepository,
        private ResourceRepository $resourceRepository,
        private ResourceManager $resourceManager,
        private PersistenceManagerInterface $persistenceManager,
    ) {
    }

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): SiteImportService
    {
        // TODO: make configurable(?)
        $processors = Processors::fromArray([
            'Run doctrine migrations' => new DoctrineMigrateProcessor($this->doctrineService),
            'Setup content repository' => new ContentRepositorySetupProcessor($serviceFactoryDependencies->contentRepository),
            'Create Neos sites' => new SiteCreationProcessor($this->siteRepository),
            // TODO create live workspace, etc
            'Import events' => new EventStoreImportProcessor(false, $serviceFactoryDependencies->eventStore, $serviceFactoryDependencies->eventNormalizer, null),
            'Import assets' => new AssetRepositoryImportProcessor($this->assetRepository, $this->resourceRepository, $this->resourceManager, $this->persistenceManager),
        ]);
        return new SiteImportService(
            $processors,
            $this->packageManager,
        );
    }
}
