<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter;

use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HypergraphProjection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\NodeFactory;
use Neos\ContentGraph\PostgreSQLAdapter\Infrastructure\PostgresDbalClientInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Core\Projection\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Core\Projection\Projections;

/**
 * @implements ProjectionFactoryInterface<HypergraphProjection>
 * @api
 */
final class HypergraphProjectionFactory implements ProjectionFactoryInterface
{
    public function __construct(
        private readonly PostgresDbalClientInterface $dbalClient
    ) {
    }

    public static function graphProjectionTableNamePrefix(
        ContentRepositoryId $contentRepositoryId
    ): string {
        return sprintf('cr_%s_p_hypergraph', $contentRepositoryId);
    }

    public function build(
        ProjectionFactoryDependencies $projectionFactoryDependencies,
        array $options,
        CatchUpHookFactoryInterface $catchUpHookFactory,
        Projections $projectionsSoFar
    ): HypergraphProjection {
        $tableNamePrefix = self::graphProjectionTableNamePrefix(
            $projectionFactoryDependencies->contentRepositoryId
        );

        return new HypergraphProjection(
            $projectionFactoryDependencies->eventNormalizer,
            $this->dbalClient,
            $catchUpHookFactory,
            new NodeFactory(
                $projectionFactoryDependencies->contentRepositoryId,
                $projectionFactoryDependencies->nodeTypeManager,
                $projectionFactoryDependencies->propertyConverter
            ),
            $projectionFactoryDependencies->nodeTypeManager,
            $tableNamePrefix
        );
    }
}
