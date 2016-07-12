<?php
namespace TYPO3\Neos\Aspects;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class TypoScriptCachingAspect
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
     */
    protected $typoScriptCache;

    /**
     * @Flow\Around("setting(TYPO3.Neos.typoScript.enableObjectTreeCache) && method(TYPO3\Neos\Domain\Service\TypoScriptService->getMergedTypoScriptObjectTree())")
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
     * @return mixed
     */
    public function cacheGetMergedTypoScriptObjectTree(JoinPointInterface $joinPoint)
    {
        $currentSiteNode = $joinPoint->getMethodArgument('startNode');
        $cacheIdentifier = str_replace('.', '_', $currentSiteNode->getContext()->getCurrentSite()->getSiteResourcesPackageKey());

        if ($this->typoScriptCache->has($cacheIdentifier)) {
            $typoScriptObjectTree = $this->typoScriptCache->get($cacheIdentifier);
        } else {
            $typoScriptObjectTree = $joinPoint->getAdviceChain()->proceed($joinPoint);
            $this->typoScriptCache->set($cacheIdentifier, $typoScriptObjectTree);
        }

        return $typoScriptObjectTree;
    }
}
