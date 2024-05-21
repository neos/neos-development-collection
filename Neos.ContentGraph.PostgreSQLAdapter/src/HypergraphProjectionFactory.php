<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HypergraphProjection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\ContentGraphFinder;
use Neos\ContentRepository\Core\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Core\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

/**
 * @implements ProjectionFactoryInterface<HypergraphProjection>
 * @api
 */
final class HypergraphProjectionFactory implements ProjectionFactoryInterface
{
    public function __construct(
        private readonly Connection $dbal,
    ) {
    }

    public static function graphProjectionTableNamePrefix(
        ContentRepositoryId $contentRepositoryId
    ): string {
        return sprintf('cr_%s_p_hypergraph', $contentRepositoryId->value);
    }

    public function build(
        ProjectionFactoryDependencies $projectionFactoryDependencies,
        array $options,
    ): HypergraphProjection {
        $tableNamePrefix = self::graphProjectionTableNamePrefix(
            $projectionFactoryDependencies->contentRepositoryId
        );

        $nodeFactory = new NodeFactory(
            $projectionFactoryDependencies->contentRepositoryId,
            $projectionFactoryDependencies->nodeTypeManager,
            $projectionFactoryDependencies->propertyConverter
        );

        return new HypergraphProjection(
            $this->dbal,
            $tableNamePrefix,
            new ContentGraphFinder(new ContentHyperGraphFactory($this->dbal, $nodeFactory, $projectionFactoryDependencies->contentRepositoryId, $projectionFactoryDependencies->nodeTypeManager, $tableNamePrefix))
        );
    }
}
