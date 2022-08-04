<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ProjectionIntegrityViolationDetector;
use Neos\ContentRepository\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Projection\ContentGraph\ProjectionIntegrityViolationDetectionRunner;

class DoctrineDbalProjectionIntegrityViolationDetectionRunnerFactory implements ContentRepositoryServiceFactoryInterface
{
    public function __construct(
        private readonly DbalClientInterface $dbalClient
    ) {
    }

    public function build(
        ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies
    ): ContentRepositoryServiceInterface {
        return new ProjectionIntegrityViolationDetectionRunner(
            new ProjectionIntegrityViolationDetector(
                $this->dbalClient,
                DoctrineDbalContentGraphProjectionFactory::graphProjectionTableNamePrefix(
                    $serviceFactoryDependencies->contentRepositoryIdentifier
                )
            )
        );
    }
}
