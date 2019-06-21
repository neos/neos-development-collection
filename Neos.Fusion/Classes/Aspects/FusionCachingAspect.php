<?php
namespace Neos\Fusion\Aspects;

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
use Neos\Cache\Frontend\VariableFrontend;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class FusionCachingAspect
{
    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $fusionCache;

    /**
     * @Flow\Around("setting(Neos.Fusion.enableObjectTreeCache) && method(Neos\Fusion\View\FusionView->getMergedFusionObjectTree())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return mixed
     */
    public function cacheGetMergedFusionObjectTree(JoinPointInterface $joinPoint)
    {
        $fusionPathPatterns = $joinPoint->getProxy()->getOption('fusionPathPatterns');
        $cacheIdentifier = md5(serialize($fusionPathPatterns));

        if ($this->fusionCache->has($cacheIdentifier)) {
            $fusionObjectTree = $this->fusionCache->get($cacheIdentifier);
        } else {
            $fusionObjectTree = $joinPoint->getAdviceChain()->proceed($joinPoint);
            $this->fusionCache->set($cacheIdentifier, $fusionObjectTree);
        }

        return $fusionObjectTree;
    }
}
