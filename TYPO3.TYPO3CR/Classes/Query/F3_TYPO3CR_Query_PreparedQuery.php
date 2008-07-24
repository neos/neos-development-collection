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
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 */

/**
 * A prepared query. A new prepared query is created by calling
 * QueryManager->createPreparedQuery.
 *
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Query_PreparedQuery extends F3_TYPO3CR_Query_Query implements F3_PHPCR_Query_PreparedQueryInterface {

	/**
	 * Binds the given value to the variable named $varName.
	 *
	 * @param string $varName name of variable in query
	 * @param F3_PHPCR_ValueInterface $value value to bind
	 * @return void
	 * @throws InvalidArgumentException if $varName is not a valid variable in this query.
	 * @throws RepositoryException if an error occurs.
	 */
	public function bindValue($varName, F3_PHPCR_ValueInterface $value) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1216897669);
	}

}

?>