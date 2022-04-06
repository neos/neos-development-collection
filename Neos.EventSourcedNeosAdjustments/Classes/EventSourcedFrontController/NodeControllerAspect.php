<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\EventSourcedFrontController;

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
class NodeControllerAspect
{

    /**
     * Hooks into standard content element wrapping to render those attributes needed for the package to identify
     * nodes and Fusion paths
     *
     * @Flow\Around("method(Neos\Neos\Controller\Frontend\NodeController->processRequest())")
     * @param JoinPointInterface $joinPoint the join point
     * @return mixed
     */
    public function replaceNodeController(JoinPointInterface $joinPoint)
    {
        $controller = new EventSourcedNodeController();
        $controller->processRequest(
            $joinPoint->getMethodArgument('request'),
            $joinPoint->getMethodArgument('response')
        );
    }
}
