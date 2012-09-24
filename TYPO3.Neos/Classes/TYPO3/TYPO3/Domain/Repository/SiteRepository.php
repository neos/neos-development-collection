<?php
namespace TYPO3\TYPO3\Domain\Repository;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
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
class SiteRepository extends \TYPO3\FLOW3\Persistence\Repository {

	/**
	 * Finds the first site
	 *
	 * @return \TYPO3\TYPO3\Domain\Model\Site The first site or NULL if none exists
	 * @api
	 */
	public function findFirst() {
		return $this->createQuery()->execute()->getFirst();
	}
}

?>