<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentStreamLayerFinder;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\DimensionSpacePointsRepository;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Core\Factory\SubscriberFactoryDependencies;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjectionFactoryInterface;
use Neos\ContentRepository\Dbal\MysqlPlatformContentRepositoryLocker;

/**
 * Use this class as ProjectionFactory in your configuration to construct a content graph
 *
 * @api
 */
final class DoctrineDbalContentGraphProjectionFactory implements ContentGraphProjectionFactoryInterface
{
    public function __construct(
        private readonly Connection $dbal,
    ) {
    }

    public function build(
        SubscriberFactoryDependencies $projectionFactoryDependencies,
    ): DoctrineDbalContentGraphProjection {
        if (!$this->dbal->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            throw new \RuntimeException(sprintf('Cannot build content graph for non mariadb/mysql connection %s', $this->dbal->getDatabasePlatform()::class), 1780672272);
        }

        $tableNames = ContentGraphTableNames::create(
            $projectionFactoryDependencies->contentRepositoryId
        );

        $dimensionSpacePointsRepository = new DimensionSpacePointsRepository($this->dbal, $tableNames);
        $contentStreamLayerFinder = new ContentStreamLayerFinder($this->dbal, $tableNames);

        $nodeFactory = new NodeFactory(
            $projectionFactoryDependencies->contentRepositoryId,
            $projectionFactoryDependencies->getPropertyConverter(),
            $dimensionSpacePointsRepository
        );

        $contentGraphReadModel = new ContentGraphReadModelAdapter(
            $this->dbal,
            $nodeFactory,
            $projectionFactoryDependencies->contentRepositoryId,
            $projectionFactoryDependencies->nodeTypeManager,
            $tableNames
        );

        return new DoctrineDbalContentGraphProjection(
            $this->dbal,
            MysqlPlatformContentRepositoryLocker::forContentRepositoryAndConnection(
                $projectionFactoryDependencies->contentRepositoryId,
                $this->dbal
            ),
            new ProjectionContentGraph(
                $this->dbal,
                $tableNames
            ),
            $tableNames,
            $dimensionSpacePointsRepository,
            $contentStreamLayerFinder,
            $contentGraphReadModel
        );
    }
}
