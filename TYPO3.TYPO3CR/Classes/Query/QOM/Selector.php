<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Query\QOM;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Selects a subset of the nodes in the repository based on node type.
 *
 * A selector selects every node in the repository, subject to access control
 * constraints, that satisfies at least one of the following conditions:
 *
 * the node's primary node type is nodeType, or
 * the node's primary node type is a subtype of nodeType, or
 * the node has a mixin node type that is nodeType, or
 * the node has a mixin node type that is a subtype of nodeType.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Selector implements \F3\PHPCR\Query\QOM\SelectorInterface {

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
	 * @param string $selectorName
	 * @param string $nodeTypeName
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($selectorName, $nodeTypeName) {
		$this->selectorName = $selectorName;
		$this->nodeTypeName = $nodeTypeName;
	}

	/**
	 * Gets the name of the required node type.
	 *
	 * @return string the node type name; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getNodeTypeName() {
		return $this->nodeTypeName;
	}

	/**
	 * Gets the selector name.
	 * A selector's name can be used elsewhere in the query to identify the selector.
	 *
	 * @return string the selector name; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getSelectorName() {
		return $this->selectorName;
	}

}

?>