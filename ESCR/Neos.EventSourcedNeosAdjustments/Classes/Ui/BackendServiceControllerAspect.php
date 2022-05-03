<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\Ui;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedNeosAdjustments\Ui\Controller\BackendServiceController;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class BackendServiceControllerAspect
{

     /**
     * @Flow\Around("method(Neos\Neos\Ui\Controller\BackendServiceController->processRequest())")
     * @param JoinPointInterface $joinPoint the join point
     * @return void
     */
    public function replaceBackendServiceController(JoinPointInterface $joinPoint)
    {
        $controller = new BackendServiceController();
        $controller->processRequest(
            $joinPoint->getMethodArgument('request'),
            $joinPoint->getMethodArgument('response')
        );
    }
}
