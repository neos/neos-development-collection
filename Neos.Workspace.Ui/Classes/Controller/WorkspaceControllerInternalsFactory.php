<?php

/*
 * This file is part of the Neos.Workspace.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\Controller;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;

/**
 * @implements ContentRepositoryServiceFactoryInterface<WorkspaceControllerInternals>
 */
class WorkspaceControllerInternalsFactory implements ContentRepositoryServiceFactoryInterface
{
    public function build(
        ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies
    ): WorkspaceControllerInternals {
        return new WorkspaceControllerInternals(
            $serviceFactoryDependencies->contentDimensionSource,
        );
    }
}
