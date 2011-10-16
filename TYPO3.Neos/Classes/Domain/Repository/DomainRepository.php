<?php
namespace TYPO3\TYPO3\Domain\Repository;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The Site Repository
 *
 * @FLOW3\Scope("singleton")
 * @api
 */
class DomainRepository extends \TYPO3\FLOW3\Persistence\Repository {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3\Domain\Service\DomainMatchingStrategy
	 */
	protected $domainMatchingStrategy;

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

}
?>