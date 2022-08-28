<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Service;

use Neos\ContentRepository\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Factory\ContentRepositoryServiceFactoryInterface;

/**
 * @implements ContentRepositoryServiceFactoryInterface<WorkspaceMaintenanceService>
 * @api
 */
class WorkspaceMaintenanceServiceFactory implements ContentRepositoryServiceFactoryInterface
{
    public function build(
        ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies
    ): WorkspaceMaintenanceService {
        return new WorkspaceMaintenanceService(
            $serviceFactoryDependencies->contentRepository,
        );
    }
}
