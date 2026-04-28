<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionIntegrityViolationDetector;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ProjectionIntegrityViolationDetectionRunner;

/**
 * @implements ContentRepositoryServiceFactoryInterface<ProjectionIntegrityViolationDetectionRunner>
 * @internal
 */
class PostgresProjectionIntegrityViolationDetectionRunnerFactory implements ContentRepositoryServiceFactoryInterface
{
    public function __construct(
        private readonly Connection $dbal,
    ) {
    }

    public function build(
        ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies
    ): ProjectionIntegrityViolationDetectionRunner {
        return new ProjectionIntegrityViolationDetectionRunner(
            new ProjectionIntegrityViolationDetector(
                $this->dbal,
                ContentGraphTableNames::create(
                    $serviceFactoryDependencies->contentRepositoryId
                )
            )
        );
    }
}
