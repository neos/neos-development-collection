<?php

namespace Neos\EventSourcedNeosAdjustments\Fluid;

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

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class ViewHelperReplacementAspect
{

    /**
     * @Flow\Inject
     * @var EventSourcedContentRepositoryFeatures
     */
    protected $eventSourcedContentRepositoryFeatures;

    protected $viewHelperClassMapping = [
        \Neos\Neos\ViewHelpers\Link\NodeViewHelper::class => \Neos\EventSourcedNeosAdjustments\Fluid\ViewHelpers\Link\NodeViewHelper::class,
        \Neos\Neos\ViewHelpers\Uri\NodeViewHelper::class => \Neos\EventSourcedNeosAdjustments\Fluid\ViewHelpers\Uri\NodeViewHelper::class
    ];

    /**
     * @Flow\Around("method(Neos\FluidAdaptor\Core\ViewHelper\ViewHelperResolver->createViewHelperInstanceFromClassName())")
     * @param JoinPointInterface $joinPoint the join point
     * @return mixed
     */
    public function createViewHelperInstanceFromClassName(JoinPointInterface $joinPoint)
    {
        if ($this->eventSourcedContentRepositoryFeatures->isNewRoutingEnabled()) {
            $viewHelperClassName = $joinPoint->getMethodArgument('viewHelperClassName');

            if (isset($this->viewHelperClassMapping[$viewHelperClassName])) {
                $joinPoint->setMethodArgument('viewHelperClassName', $this->viewHelperClassMapping[$viewHelperClassName]);
            }
        }
        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }
}
