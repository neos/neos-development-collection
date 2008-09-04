<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 */

/**
 * The Site Repository
 *
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @repository
 */
class F3_TYPO3_Domain_Model_SiteRepository extends F3_FLOW3_Persistence_Repository {

	/**
	 * Finds a site by its identifier
	 *
	 * @param string The UUID of the site
	 * @return F3_TYPO3_Domain_Model_Site The site or NULL if it doesn't exist
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findByIdentifier($identifier) {
		$query = $this->queryFactory->create('F3_TYPO3_Domain_Model_Site');
		$sites = $query->matching($query->equals('identifier', $identifier))->execute();
		return (is_array($sites)) ? array_shift($sites) : NULL;
	}
}

?>