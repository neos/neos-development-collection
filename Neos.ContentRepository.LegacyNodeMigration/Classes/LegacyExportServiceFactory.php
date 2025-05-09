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
use Neos\Flow\Property\PropertyMapper;

/**
 * @implements ContentRepositoryServiceFactoryInterface<LegacyExportService>
 */
class LegacyExportServiceFactory implements ContentRepositoryServiceFactoryInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $resourcesPath,
        private readonly PropertyMapper $propertyMapper,
        private readonly RootNodeTypeMapping $rootNodeTypeMapping,
    ) {
    }

    public function build(
        ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies
    ): LegacyExportService {
        return new LegacyExportService(
            $this->connection,
            $this->resourcesPath,
            $serviceFactoryDependencies->interDimensionalVariationGraph,
            $serviceFactoryDependencies->nodeTypeManager,
            $this->propertyMapper,
            $serviceFactoryDependencies->eventNormalizer,
            $serviceFactoryDependencies->propertyConverter,
            $this->rootNodeTypeMapping,
        );
    }
}
