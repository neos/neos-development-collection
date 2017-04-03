<?php
namespace Neos\Neos\Domain\Repository;

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
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\Repository;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Service\DomainMatchingStrategy;

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
        'hostname' => QueryInterface::ORDER_ASCENDING
    );

    /**
     * Finds all active domains matching the given hostname.
     *
     * Their order is determined by how well they match, best match first.
     *
     * @param string $hostname Hostname the domain should match with (eg. "localhost" or "www.neos.io")
     * @param boolean $onlyActive Only include active domains
     * @return array An array of matching domains
     * @api
     */
    public function findByHost($hostname, $onlyActive = false)
    {
        $domains = $onlyActive === true ? $this->findByActive(true)->toArray() : $this->findAll()->toArray();
        return $this->domainMatchingStrategy->getSortedMatches($hostname, $domains);
    }

    /**
     * Find the best matching active domain for the given hostname.
     *
     * @param string $hostname Hostname the domain should match with (eg. "localhost" or "www.neos.io")
     * @param boolean $onlyActive Only include active domains
     * @return Domain
     * @api
     */
    public function findOneByHost($hostname, $onlyActive = false)
    {
        $allMatchingDomains = $this->findByHost($hostname, $onlyActive);
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
