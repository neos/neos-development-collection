<?php
namespace Neos\Neos\Aspects;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Exception as CacheException;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;

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
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Around("setting(Neos.Neos.fusion.enableObjectTreeCache) && method(Neos\Neos\Domain\Service\FusionService->getMergedFusionObjectTree())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return mixed
     * @throws CacheException
     */
    public function cacheGetMergedFusionObjectTree(JoinPointInterface $joinPoint)
    {
        $currentSiteNode = $joinPoint->getMethodArgument('startNode');
        $siteResourcesPackageKey = $this->getSiteForSiteNode($currentSiteNode)->getSiteResourcesPackageKey();
        $cacheIdentifier = str_replace('.', '_', $siteResourcesPackageKey);

        if ($this->fusionCache->has($cacheIdentifier)) {
            $fusionObjectTree = $this->fusionCache->get($cacheIdentifier);
        } else {
            $fusionObjectTree = $joinPoint->getAdviceChain()->proceed($joinPoint);
            $this->fusionCache->set($cacheIdentifier, $fusionObjectTree);
        }

        return $fusionObjectTree;
    }

    /**
     * Get a site for the given site node.
     *
     * @see \Neos\Neos\Domain\Service\FusionService::getSiteForSiteNode()
     *
     * @param TraversableNodeInterface $siteNode
     * @return Site
     */
    protected function getSiteForSiteNode(TraversableNodeInterface $siteNode)
    {
        return $this->siteRepository->findOneByNodeName((string)$siteNode->getNodeName());
    }
}
