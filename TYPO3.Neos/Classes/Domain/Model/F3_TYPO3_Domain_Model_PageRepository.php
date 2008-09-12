<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::Domain::Model;

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
 * @version $Id$
 */

/**
 * The Page Repository contains all Pages and provides methods to manage them.
 *
 * @package TYPO3
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @repository
 */
class PageRepository extends F3::FLOW3::Persistence::Repository {

	/**
	 * Finds all pages
	 *
	 * @return array An array of the found page objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findAll() {
		$query = $this->queryFactory->create('F3::TYPO3::Domain::Model::Page');
		$pages = $query->execute();
		return $pages;
	}
}

?>