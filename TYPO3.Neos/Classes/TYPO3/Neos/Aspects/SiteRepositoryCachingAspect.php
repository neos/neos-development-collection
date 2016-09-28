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
use TYPO3\Flow\Utility\Environment;
use TYPO3\Neos\Domain\Model\Domain;
use TYPO3\Neos\Domain\Model\Site;

/**
 * Aspect to memoize values from SiteRepository without the overhead of a query cache
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class SiteRepositoryCachingAspect
{
    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @var Site|boolean
     */
    protected $firstOnlineSite = false;

    /**
     * @var Domain|boolean
     */
    protected $domainForActiveRequest = false;

    /**
     * @Flow\Around("method(TYPO3\Neos\Domain\Repository\SiteRepository->findFirstOnline())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return mixed
     */
    public function cacheFirstOnlineSite(JoinPointInterface $joinPoint)
    {
        if ($this->firstOnlineSite === false || $this->environment->getContext()->isTesting()) {
            $site = $joinPoint->getAdviceChain()->proceed($joinPoint);
            $this->firstOnlineSite = $site;
        }
        return $this->firstOnlineSite;
    }

    /**
     * @Flow\Around("method(TYPO3\Neos\Domain\Repository\DomainRepository->findOneByActiveRequest())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return mixed
     */
    public function cacheDomainForActiveRequest(JoinPointInterface $joinPoint)
    {
        if ($this->domainForActiveRequest === false || $this->environment->getContext()->isTesting()) {
            $domain = $joinPoint->getAdviceChain()->proceed($joinPoint);
            $this->domainForActiveRequest = $domain;
        }
        return $this->domainForActiveRequest;
    }
}
