<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\DimensionSpacePointsRepository;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Core\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Core\Projection\ContentRepositoryProjectionFactoryInterface;

/**
 * Use this class as ProjectionFactory in your configuration to construct a content graph
 *
 * @api
 */
final class DoctrineDbalContentGraphProjectionFactory implements ContentRepositoryProjectionFactoryInterface
{
    public function __construct(
        private readonly Connection $dbal,
    ) {
    }

    public function build(
        ProjectionFactoryDependencies $projectionFactoryDependencies,
        array $options,
    ): DoctrineDbalContentGraphProjection {
        $tableNames = ContentGraphTableNames::create(
            $projectionFactoryDependencies->contentRepositoryId
        );

        $dimensionSpacePointsRepository = new DimensionSpacePointsRepository($this->dbal, $tableNames);

        $nodeFactory = new NodeFactory(
            $projectionFactoryDependencies->contentRepositoryId,
            $projectionFactoryDependencies->propertyConverter,
            $dimensionSpacePointsRepository
        );

        $contentRepositoryReadModel = new ContentRepositoryReadModelAdapter(
            $this->dbal,
            $nodeFactory,
            $projectionFactoryDependencies->contentRepositoryId,
            $projectionFactoryDependencies->nodeTypeManager,
            $tableNames
        );

        return new DoctrineDbalContentGraphProjection(
            $this->dbal,
            new ProjectionContentGraph(
                $this->dbal,
                $tableNames
            ),
            $tableNames,
            $dimensionSpacePointsRepository,
            $contentRepositoryReadModel
        );
    }
}
