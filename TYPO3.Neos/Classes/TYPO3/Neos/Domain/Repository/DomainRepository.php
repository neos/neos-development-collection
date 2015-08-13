<?php
namespace TYPO3\Neos\Domain\Repository;

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

/**
 * The Site Repository
 *
 * @Flow\Scope("singleton")
 * @api
 */
class DomainRepository extends \TYPO3\Flow\Persistence\Repository {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Service\DomainMatchingStrategy
	 */
	protected $domainMatchingStrategy;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * Finds all active domains matching the given host.
	 *
	 * Their order is determined by how well they match, best match first.
	 *
	 * @param string $host Host the domain should match with (eg. "localhost" or "www.typo3.org")
	 * @return array An array of matching domains
	 * @api
	 */
	public function findByHost($host) {
		return $this->domainMatchingStrategy->getSortedMatches($host, $this->findAll()->toArray());
	}

	/**
	 * Find the best matching active domain for the given host.
	 *
	 * @param $host
	 * @return \TYPO3\Neos\Domain\Model\Domain
	 * @api
	 */
	public function findOneByHost($host) {
		$allMatchingDomains = $this->domainMatchingStrategy->getSortedMatches($host, $this->findAll()->toArray());
		return count($allMatchingDomains) ? $allMatchingDomains[0] : NULL;
	}

	/**
	 *
	 * @return \TYPO3\Neos\Domain\Model\Domain
	 */
	public function findOneByActiveRequest() {
		$matchingDomain = NULL;
		$activeRequestHandler = $this->bootstrap->getActiveRequestHandler();
		if ($activeRequestHandler instanceof \TYPO3\Flow\Http\HttpRequestHandlerInterface) {
			$matchingDomain = $this->findOneByHost($activeRequestHandler->getHttpRequest()->getUri()->getHost());
		}

		return $matchingDomain;
	}

}
