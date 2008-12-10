<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Query;

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
 * @scope prototype
 */
class PreparedQuery extends \F3\TYPO3CR\Query\Query implements \F3\PHPCR\Query\PreparedQueryInterface {

	/**
	 * @var array
	 */
	protected $boundVariableNames = array();

	/**
	 * @var array
	 */
	protected $variableValues = array();

	/**
	 * Binds the given value to the variable named $varName.
	 *
	 * @param string $varName name of variable in query
	 * @param \F3\PHPCR\ValueInterface $value value to bind
	 * @return void
	 * @throws \InvalidArgumentException if $varName is not a valid variable in this query.
	 * @throws RepositoryException if an error occurs.
	 */
	public function bindValue($varName, \F3\PHPCR\ValueInterface $value) {
		if (!array_search($varName, $this->boundVariableNames)) {
			throw new \InvalidArgumentException('Invalid variable name given to bindValue.', 1217241834);
		}

		switch ($value->getType()) {
			case \F3\PHPCR\PropertyType::STRING:
				$value = $value->getString();
				break;
			default:
				throw new \F3\PHPCR\RepositoryException('Unsupported value type in bindValue encountered.', 1218020658);
		}
		$this->variableValues[$varName] = $value;
	}

	/**
	 * Returns the values of all bound variables.
	 *
	 * @return array()
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getBoundVariableValues() {
		return $this->variableValues;
	}
}

?>