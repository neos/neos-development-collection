<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model;

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
 */
class SiteRepository extends \F3\FLOW3\Persistence\Repository {

	/**
	 * Finds a site by its identifier
	 *
	 * @param string The UUID of the site
	 * @return \F3\TYPO3\Domain\Model\Site The site or NULL if it doesn't exist
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo replace "identifier" with "id" if #1623 is resolved
	 */
	public function findById($id) {
		$query = $this->queryFactory->create();
		$sites = $query->matching($query->equals('identifier', (string)$id))->execute();
		return (is_array($sites)) ? array_shift($sites) : NULL;
	}
}

?>