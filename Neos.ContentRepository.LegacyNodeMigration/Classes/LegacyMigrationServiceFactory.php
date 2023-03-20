<?php
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration;

/*
 * This file is part of the Neos.ContentRepository.LegacyNodeMigration package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Flow\Utility\Environment;
use Neos\Media\Domain\Repository\AssetRepository;

/**
 * @implements ContentRepositoryServiceFactoryInterface<LegacyMigrationService>
 */
class LegacyMigrationServiceFactory implements ContentRepositoryServiceFactoryInterface
{

    public function __construct(
        private readonly Connection $connection,
        private readonly string $resourcesPath,
        private readonly Environment $environment,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly AssetRepository $assetRepository,
        private readonly ResourceRepository $resourceRepository,
        private readonly ResourceManager $resourceManager,
        private readonly PropertyMapper $propertyMapper,
        private readonly ContentStreamId $contentStreamId,
    )
    {
    }

    public function build(
        ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies
    ): LegacyMigrationService
    {
        return new LegacyMigrationService(
            $this->connection,
            $this->resourcesPath,
            $this->environment,
            $this->persistenceManager,
            $this->assetRepository,
            $this->resourceRepository,
            $this->resourceManager,
            $serviceFactoryDependencies->interDimensionalVariationGraph,
            $serviceFactoryDependencies->nodeTypeManager,
            $this->propertyMapper,
            $serviceFactoryDependencies->eventNormalizer,
            $serviceFactoryDependencies->propertyConverter,
            $serviceFactoryDependencies->eventStore,
            $this->contentStreamId,
        );
    }
}
