<?php

namespace Neos\EventSourcedNeosAdjustments\Fusion;

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
class FusionLoadingAspect
{

    /**
     * @Flow\Inject
     * @var EventSourcedContentRepositoryFeatures
     */
    protected $eventSourcedContentRepositoryFeatures;

    /**
     * @Flow\Around("method(Neos\Neos\Domain\Service\FusionService->getMergedFusionObjectTree())")
     * @param JoinPointInterface $joinPoint the join point
     * @return mixed
     */
    public function addCustomFusionIfActive(JoinPointInterface $joinPoint)
    {
        if ($this->eventSourcedContentRepositoryFeatures->isNewRoutingEnabled()) {
            /* @var $fusionService \Neos\Neos\Domain\Service\FusionService */
            $fusionService = $joinPoint->getProxy();
            $prependFusionIncludes = $fusionService->getPrependFusionIncludes();
            $prependFusionIncludes[] = 'resource://Neos.EventSourcedNeosAdjustments/Private/Fusion/Root.fusion';
            $fusionService->setPrependFusionIncludes($prependFusionIncludes);
        }
        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }
}
