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
 * @subpackage Domain
 * @version $Id$
 */

/**
 * A Structure Node
 *
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 * @entity
 */
class StructureNode {

	/**
	 * The identifier of this node (a UUID)
	 *
	 * @var string
	 * @identifier
	 */
	protected $id;

	/**
	 * Child nodes of this structure node
	 *
	 * @var array
	 * @reference
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected $childNodes = array();

	/**
	 * Constructs the structure node
	 *
	 * @param string $title The page title
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		$this->id = F3::FLOW3::Utility::Algorithms::generateUUID();
	}

	/**
	 * Returns the identifier of this node
	 *
	 * @return string The UUID of this structure node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Adds a child node to this node
	 *
	 * @param F3::TYPO3::Domain::Model::StructureNode $node The node to add
	 * @return void
	 */
	public function addChildNode(F3::TYPO3::Domain::Model::StructureNode $node) {
		$this->childNodes[] = $node;
	}

	/**
	 * Returns the child nodes of this node, if any.
	 *
	 * @return array An array of child nodes or an empty array if no children exist
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getChildNodes() {
		return $this->childNodes;
	}

	/**
	 * Returns a label which can be used to describe this structure node.
	 *
	 * The label is no real property of a structure node but is rendered dynamically
	 * from the content which is attached to the node.
	 *
	 * @return string A label describing the node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLabel() {
		return '[No Label]';
	}
}

?>