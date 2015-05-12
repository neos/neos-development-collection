<?php
namespace TYPO3\Neos\Aspects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class TypoScriptCachingAspect {

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
	public function cacheGetMergedTypoScriptObjectTree(JoinPointInterface $joinPoint) {
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
