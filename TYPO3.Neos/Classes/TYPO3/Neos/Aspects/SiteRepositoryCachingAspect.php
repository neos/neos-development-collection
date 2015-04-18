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
 * Aspect to memoize values from SiteRepository without the overhead of a query cache
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class SiteRepositoryCachingAspect {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \TYPO3\Neos\Domain\Model\Site|boolean
	 */
	protected $firstOnlineSite = FALSE;

	/**
	 * @var \TYPO3\Neos\Domain\Model\Domain|boolean
	 */
	protected $domainForActiveRequest = FALSE;

	/**
	 * @Flow\Around("method(TYPO3\Neos\Domain\Repository\SiteRepository->findFirstOnline())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return mixed
	 */
	public function cacheFirstOnlineSite(JoinPointInterface $joinPoint) {
		if ($this->firstOnlineSite === FALSE || $this->environment->getContext()->isTesting()) {
			$site = $joinPoint->getAdviceChain()->proceed($joinPoint);
			$this->firstOnlineSite = $site;
		}
		return $this->firstOnlineSite;
	}

	/**
	 * @Flow\Around("method(TYPO3\Neos\Domain\Repository\DomainRepository->findOneByActiveRequest())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return mixed
	 */
	public function cacheDomainForActiveRequest(JoinPointInterface $joinPoint) {
		if ($this->domainForActiveRequest === FALSE || $this->environment->getContext()->isTesting()) {
			$domain = $joinPoint->getAdviceChain()->proceed($joinPoint);
			$this->domainForActiveRequest = $domain;
		}
		return $this->domainForActiveRequest;
	}
}
