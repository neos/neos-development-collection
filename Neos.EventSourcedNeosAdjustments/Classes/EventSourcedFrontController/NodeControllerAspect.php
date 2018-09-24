<?php

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

use Neos\EventSourcedContentRepository\EventSourcedContentRepositoryFeatures;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Neos\Routing\FrontendNodeRoutePartHandler;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class NodeControllerAspect
{

    /**
     * @Flow\Inject
     * @var EventSourcedContentRepositoryFeatures
     */
    protected $eventSourcedContentRepositoryFeatures;

    /**
     * Hooks into standard content element wrapping to render those attributes needed for the package to identify
     * nodes and Fusion paths
     *
     * @Flow\Around("method(Neos\Neos\Controller\Frontend\NodeController->processRequest())")
     * @param JoinPointInterface $joinPoint the join point
     * @return mixed
     */
    public function contentElementAugmentation(JoinPointInterface $joinPoint)
    {
        if ($this->eventSourcedContentRepositoryFeatures->isNewRoutingEnabled()) {
            $controller = new EventSourcedNodeController();
            $controller->processRequest($joinPoint->getMethodArgument('request'), $joinPoint->getMethodArgument('response'));
        } else {
            // Legacy mode
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }
    }
}
