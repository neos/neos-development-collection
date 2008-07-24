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
 * Allows easy iteration through a list of Rows with nextRow as well as a skip
 * method inherited from RangeIterator.
 *
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Query_RowIterator extends F3_TYPO3CR_RangeIterator implements F3_PHPCR_Query_RowIteratorInterface {

	/**
	 * Returns the next Row in the iteration.
	 *
	 * @return F3_PHPCR_Query_RowInterface
	 * @throws OutOfBoundsException if the iterator contains no more elements.
	 * @author Karsten Dambekalns <karsten@dambekalns.de>
	 */
	public function nextRow() {
		return $this->next();
	}

}

?>