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
	 * Child nodes of this structure node
	 *
	 * @var array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected $childNodes = array();

	/**
	 * Content attached to this structure node, indexed by language and region
	 *
	 * @var array
	 */
	protected $contents;

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
	 * @param \F3\TYPO3\Domain\Model\Content\ContentInterface $content The content object
	 * @param string $language Language of the content as a BCP47, ISO 639-3 or 639-5 code (see Locale sub package)
	 * @param string $region Region for the content - an ISO 3166-1-alpha-2 code or a UN M.49 three digit code
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addContent(\F3\TYPO3\Domain\Model\Content\ContentInterface $content, $language = 'mul', $region = 'ZZ') {
		$this->contents[$language][$region][] = $content;
	}

	/**
	 * Returns the content objects attached to this structure node (if any)
	 *
	 * @return array An array of content objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContents() {
		return $this->contents;
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