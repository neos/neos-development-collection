<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration;

use Neos\ContentRepository\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\NodeMigration\Filter\FiltersFactory;
use Neos\ContentRepository\NodeMigration\Transformation\TransformationsFactory;
use Psr\Container\ContainerInterface;

/**
 * @implements ContentRepositoryServiceFactoryInterface<NodeMigrationService>
 */
class NodeMigrationServiceFactory implements ContentRepositoryServiceFactoryInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    )
    {
    }

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): NodeMigrationService
    {
        return new NodeMigrationService(
            $serviceFactoryDependencies->contentRepository,
            new FiltersFactory($this->container),
            new TransformationsFactory($this->container)
        );
    }
}
