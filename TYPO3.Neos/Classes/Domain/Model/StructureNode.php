<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
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
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected $childNodes = array();

	/**
	 * Content attached to this structure node
	 *
	 * @var \F3\TYPO3\Domain\Model\ContentInterface
	 */
	protected $content;

	/**
	 * Constructs the structure node
	 *
	 * @param string $title The page title
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		$this->id = \F3\FLOW3\Utility\Algorithms::generateUUID();
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
	 * @param \F3\TYPO3\Domain\Model\StructureNode $node The node to add
	 * @return void
	 */
	public function addChildNode(\F3\TYPO3\Domain\Model\StructureNode $node) {
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
	 * If this structure node has child nodes
	 *
	 * @return boolean TRUE if child nodes exist, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasChildNodes() {
		return (count($this->childNodes) > 0);
	}

	/**
	 * Attaches a content object to this structure node
	 *
	 * @param \F3\TYPO3\Domain\Model\ContentInterface $content The content object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContent(\F3\TYPO3\Domain\Model\ContentInterface $content) {
		$this->content = $content;
	}

	/**
	 * Returns the content object attached to this structure node (if any)
	 *
	 * @return mixed The content object or NULL if none exists
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContent() {
		return $this->content;
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
		return ($this->content !== NULL) ? $this->content->getLabel() : '[No Content]';
	}
}

?>