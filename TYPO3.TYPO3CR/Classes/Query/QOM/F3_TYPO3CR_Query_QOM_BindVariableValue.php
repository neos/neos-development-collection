<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Query\QOM;

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
 * Evaluates to the value of a bind variable.
 *
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class BindVariableValue extends \F3\TYPO3CR\Query\QOM\StaticOperand implements \F3\PHPCR\Query\QOM\BindVariableValueInterface {

	/**
	 * @var string
	 */
	protected $variableName;

	/**
	 * Constructs this BindVariableValue instance
	 *
	 * @param string $variableName
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($variableName) {
		$this->variableName = $variableName;
	}

	/**
	 * Fills an array with the names of all bound variables in the operand
	 *
	 * @param array &$boundVariableNames
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function collectBoundVariableNames(&$boundVariableNames) {
		$boundVariableNames[] = $this->variableName;
	}


	/**
	 * Gets the name of the bind variable.
	 *
	 * @return string the bind variable name; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getBindVariableName() {
		return $this->variableName;
	}

}

?>