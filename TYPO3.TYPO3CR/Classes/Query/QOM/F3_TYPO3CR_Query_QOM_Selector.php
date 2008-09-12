<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::Query::QOM;

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
 * A Selector for the JSR-283 QOM
 *
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Selector implements F3::PHPCR::Query::QOM::SelectorInterface {

	/**
	 * @var string
	 */
	protected $nodeTypeName;

	/**
	 * @var string
	 */
	protected $selectorName;

	/**
	 * Constructs the Selector instance
	 *
	 * @param string $nodeTypeName
	 * @param string $selectorName
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($nodeTypeName, $selectorName = '') {
		$this->nodeTypeName = $nodeTypeName;
		$this->selectorName = $selectorName;
	}

	/**
	 * Gets the name of the required node type.
	 *
	 * @return string the node type name; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getNodeTypeName() {
		return $this->nodeTypeName;
	}

	/**
	 * Gets the selector name.
	 * A selector's name can be used elsewhere in the query to identify the selector.
	 *
	 * @return the selector name; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getSelectorName() {
		return $this->selectorName;
	}

}

?>