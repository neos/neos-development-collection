<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\PostgresContentGraphProjection;
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
        $nodeFactory = new NodeFactory(
            $projectionFactoryDependencies->contentRepositoryId,
            $projectionFactoryDependencies->getPropertyConverter()
        );

        return new PostgresContentGraphProjection(
            $this->dbal,
            $projectionFactoryDependencies->contentRepositoryId,
            new ContentHyperGraphReadModelAdapter(
                $this->dbal,
                $nodeFactory,
                $projectionFactoryDependencies->contentRepositoryId,
                $projectionFactoryDependencies->nodeTypeManager
            )
        );
    }
}
