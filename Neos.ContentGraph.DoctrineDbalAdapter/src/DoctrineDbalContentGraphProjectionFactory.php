<?php
declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Projection\CatchUpHandlerFactoryInterface;
use Neos\ContentRepository\Projection\ContentGraph\ContentGraphProjection;
use Neos\ContentRepository\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Projection\ProjectionInterface;
use Neos\ContentRepository\Projection\Projections;

final class DoctrineDbalContentGraphProjectionFactory implements ProjectionFactoryInterface
{
    public function __construct(
        private readonly DbalClientInterface $dbalClient
    ) {
    }

    public function build(ProjectionFactoryDependencies $projectionFactoryDependencies, array $options, CatchUpHandlerFactoryInterface $catchUpHandlerFactory, Projections $projectionsSoFar): ProjectionInterface
    {
        $tableNamePrefix = sprintf('neos_cr_%s_projection_graph', $projectionFactoryDependencies->contentRepositoryIdentifier);

        return new ContentGraphProjection(
            new DoctrineDbalContentGraphProjection(
                $projectionFactoryDependencies->eventNormalizer,
                $this->dbalClient,
                new NodeFactory(
                    $projectionFactoryDependencies->nodeTypeManager,
                    $projectionFactoryDependencies->propertyConverter
                ),
                new ProjectionContentGraph(
                    $this->dbalClient,
                    $tableNamePrefix
                ),
                $catchUpHandlerFactory,

                $tableNamePrefix
            )
        );
    }
}
