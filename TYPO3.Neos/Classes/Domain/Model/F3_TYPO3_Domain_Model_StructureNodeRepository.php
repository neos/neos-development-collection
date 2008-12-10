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
 * @version $Id$
 */

/**
 * The Structure Node Repository contains all Structure Nodes and provides methods to manage them.
 *
 * @package TYPO3
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class StructureNodeRepository extends \F3\FLOW3\Persistence\Repository {

	/**
	 * Returns the structure node matching the given id
	 *
	 * @param string $id The UUID of the node in question
	 * @return mixed The node or NULL
	 * @todo replace "identifier" with "id" if #1623 is resolved
	 */
	public function findById($id) {
		$query = $this->createQuery();
		$nodes =  $query->matching($query->equals('id', $id))->execute();
		return (is_array($nodes)) ? array_shift($nodes) : NULL;
	}
}

?>