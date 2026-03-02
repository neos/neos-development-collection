<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\PostgresContentGraphProjection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\SchemaBuilder\HypergraphSchemaBuilder;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\Factory\SubscriberFactoryDependencies;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjectionFactoryInterface;

/**
 * @api
 */
final readonly class PostgresContentGraphProjectionFactory implements ContentGraphProjectionFactoryInterface
{
    public function __construct(
        private Connection $dbal,
    ) {
    }

    public function build(
        SubscriberFactoryDependencies $projectionFactoryDependencies,
    ): PostgresContentGraphProjection {
        // Register custom Doctrine types early so they are available for all
        // subsequent schema operations (setUp, reset, status, ...).
        HypergraphSchemaBuilder::registerTypes($this->dbal);

        $nodeFactory = new NodeFactory(
            $projectionFactoryDependencies->contentRepositoryId,
            $projectionFactoryDependencies->getPropertyConverter()
        );

        return new PostgresContentGraphProjection(
            $this->dbal,
            $projectionFactoryDependencies->contentRepositoryId,
            new ContentHyperGraphReadModelAdapter(
                $this->dbal,
                $projectionFactoryDependencies->getPropertyConverter(),
                $nodeFactory,
                $projectionFactoryDependencies->contentRepositoryId,
                $projectionFactoryDependencies->nodeTypeManager
            )
        );
    }
}
