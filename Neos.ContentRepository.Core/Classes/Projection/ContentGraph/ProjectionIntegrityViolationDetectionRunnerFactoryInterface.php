<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;

/**
 * @extends ContentRepositoryServiceFactoryInterface<ProjectionIntegrityViolationDetectionRunner>
 * @internal only API for custom content repository integrations
 */
interface ProjectionIntegrityViolationDetectionRunnerFactoryInterface extends ContentRepositoryServiceFactoryInterface
{
}
