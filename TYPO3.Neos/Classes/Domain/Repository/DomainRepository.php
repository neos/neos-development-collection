<?php
namespace TYPO3\Neos\Domain\Repository;

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
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Http\HttpRequestHandlerInterface;
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Flow\Persistence\Repository;
use TYPO3\Neos\Domain\Model\Domain;
use TYPO3\Neos\Domain\Service\DomainMatchingStrategy;

/**
 * The Site Repository
 *
 * @Flow\Scope("singleton")
 * @api
 */
class DomainRepository extends Repository
{
    /**
     * @Flow\Inject
     * @var DomainMatchingStrategy
     */
    protected $domainMatchingStrategy;

    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var array
     */
    protected $defaultOrderings = array(
        'site' => QueryInterface::ORDER_ASCENDING,
        'hostPattern' => QueryInterface::ORDER_ASCENDING
    );

    /**
     * Finds all active domains matching the given host.
     *
     * Their order is determined by how well they match, best match first.
     *
     * @param string $host Host the domain should match with (eg. "localhost" or "www.neos.io")
     * @param boolean $onlyActive Only include active domains
     * @return array An array of matching domains
     * @api
     */
    public function findByHost($host, $onlyActive = false)
    {
        $domains = $onlyActive === true ? $this->findByActive(true)->toArray() : $this->findAll()->toArray();
        return $this->domainMatchingStrategy->getSortedMatches($host, $domains);
    }

    /**
     * Find the best matching active domain for the given host.
     *
     * @param string $host Host the domain should match with (eg. "localhost" or "www.neos.io")
     * @param boolean $onlyActive Only include active domains
     * @return Domain
     * @api
     */
    public function findOneByHost($host, $onlyActive = false)
    {
        $allMatchingDomains = $this->findByHost($host, $onlyActive);
        return count($allMatchingDomains) > 0 ? $allMatchingDomains[0] : null;
    }

    /**
     * @return Domain
     */
    public function findOneByActiveRequest()
    {
        $matchingDomain = null;
        $activeRequestHandler = $this->bootstrap->getActiveRequestHandler();
        if ($activeRequestHandler instanceof HttpRequestHandlerInterface) {
            $matchingDomain = $this->findOneByHost($activeRequestHandler->getHttpRequest()->getUri()->getHost(), true);
        }

        return $matchingDomain;
    }
}
