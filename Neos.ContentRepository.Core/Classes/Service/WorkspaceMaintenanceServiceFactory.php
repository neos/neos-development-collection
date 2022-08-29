<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;

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
