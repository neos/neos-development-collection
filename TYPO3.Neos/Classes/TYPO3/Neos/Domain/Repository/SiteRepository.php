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
class SiteRepository extends \TYPO3\Flow\Persistence\Repository {

	/**
	 * Finds the first site
	 *
	 * @return \TYPO3\Neos\Domain\Model\Site The first site or NULL if none exists
	 * @api
	 */
	public function findFirst() {
		return $this->createQuery()->execute()->getFirst();
	}

	/**
	 * Find all sites with status "online"
	 *
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface
	 */
	public function findOnline() {
		return $this->findByState(\TYPO3\Neos\Domain\Model\Site::STATE_ONLINE);
	}

	/**
	 * Find first site with status "online"
	 *
	 * @return \TYPO3\Neos\Domain\Model\Site
	 */
	public function findFirstOnline() {
		return $this->findOnline()->getFirst();
	}

}
