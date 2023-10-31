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

namespace Neos\Workspace\Ui;

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
//use Neos\Neos\Service\PublishingService;
use Neos\Workspace\Ui\Service\WorkspaceActivityService;

class Package extends BasePackage
{
    public function boot(Bootstrap $bootstrap): void
    {
        // TODO: Cleanup. This doesn't work anymore. I guess the workspace projection should be extended if more details should be known about the workspace.
//        $dispatcher = $bootstrap->getSignalSlotDispatcher();

//        $dispatcher->connect(
//            PublishingService::class,
//            'nodePublished',
//            WorkspaceActivityService::class,
//            'nodePublished'
//        );
//
//        $dispatcher->connect(
//            PublishingService::class,
//            'nodeDiscarded',
//            WorkspaceActivityService::class,
//            'nodeDiscarded'
//        );
    }
}
