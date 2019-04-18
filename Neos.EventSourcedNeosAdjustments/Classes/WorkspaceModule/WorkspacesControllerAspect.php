<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\WorkspaceModule;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class WorkspacesControllerAspect
{

     /**
     * @Flow\Around("method(Neos\Neos\Controller\Module\Management\WorkspacesController->processRequest())")
     * @param JoinPointInterface $joinPoint the join point
     * @return mixed
     */
    public function replaceBackendController(JoinPointInterface $joinPoint)
    {
        $controller = new WorkspacesController();
        return $controller->processRequest($joinPoint->getMethodArgument('request'), $joinPoint->getMethodArgument('response'));
    }
}
