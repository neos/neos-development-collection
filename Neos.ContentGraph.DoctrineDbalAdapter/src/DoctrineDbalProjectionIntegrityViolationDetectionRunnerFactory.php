<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ProjectionIntegrityViolationDetector;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ProjectionIntegrityViolationDetectionRunner;

/**
 * @implements ContentRepositoryServiceFactoryInterface<ProjectionIntegrityViolationDetectionRunner>
 * @internal
 */
class DoctrineDbalProjectionIntegrityViolationDetectionRunnerFactory implements ContentRepositoryServiceFactoryInterface
{
    public function __construct(
        private readonly DbalClientInterface $dbalClient
    ) {
    }

    public function build(
        ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies
    ): ProjectionIntegrityViolationDetectionRunner {
        return new ProjectionIntegrityViolationDetectionRunner(
            new ProjectionIntegrityViolationDetector(
                $this->dbalClient,
                ContentGraphTableNames::create(
                    $serviceFactoryDependencies->contentRepositoryId
                )
            )
        );
    }
}
