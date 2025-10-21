<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HypergraphProjection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\Factory\SubscriberFactoryDependencies;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjectionFactoryInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

/**
 * @api
 */
final class HypergraphProjectionFactory implements ContentGraphProjectionFactoryInterface
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
        SubscriberFactoryDependencies $projectionFactoryDependencies,
    ): HypergraphProjection {
        $tableNamePrefix = self::graphProjectionTableNamePrefix(
            $projectionFactoryDependencies->contentRepositoryId
        );

        $nodeFactory = new NodeFactory(
            $projectionFactoryDependencies->contentRepositoryId,
            $projectionFactoryDependencies->getPropertyConverter()
        );

        return new HypergraphProjection(
            $this->dbal,
            $tableNamePrefix,
            new ContentHyperGraphReadModelAdapter($this->dbal, $nodeFactory, $projectionFactoryDependencies->contentRepositoryId, $projectionFactoryDependencies->nodeTypeManager, $tableNamePrefix)
        );
    }
}
