<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HypergraphProjection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\NodeFactory;
use Neos\ContentGraph\PostgreSQLAdapter\Infrastructure\PostgresDbalClientInterface;
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;
use Neos\ContentRepository\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Projection\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Projection\ContentGraph\ContentGraphProjection;
use Neos\ContentRepository\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Projection\ProjectionInterface;
use Neos\ContentRepository\Projection\Projections;

/**
 * @implements ProjectionFactoryInterface<HypergraphProjection>
 */
final class HypergraphProjectionFactory implements ProjectionFactoryInterface
{
    public function __construct(
        private readonly PostgresDbalClientInterface $dbalClient
    ) {
    }

    public static function graphProjectionTableNamePrefix(
        ContentRepositoryIdentifier $contentRepositoryIdentifier
    ): string {
        return sprintf('cr_%s_p_hypergraph', $contentRepositoryIdentifier);
    }

    public function build(
        ProjectionFactoryDependencies $projectionFactoryDependencies,
        array $options,
        CatchUpHookFactoryInterface $catchUpHookFactory,
        Projections $projectionsSoFar
    ): HypergraphProjection {
        $tableNamePrefix = self::graphProjectionTableNamePrefix(
            $projectionFactoryDependencies->contentRepositoryIdentifier
        );

        return new HypergraphProjection(
            // @phpstan-ignore-next-line
                $projectionFactoryDependencies->eventNormalizer,
                $this->dbalClient,
                $catchUpHookFactory,
                new NodeFactory(
                    $projectionFactoryDependencies->contentRepositoryIdentifier,
                    $projectionFactoryDependencies->nodeTypeManager,
                    $projectionFactoryDependencies->propertyConverter
                ),
                $tableNamePrefix
        );
    }
}
