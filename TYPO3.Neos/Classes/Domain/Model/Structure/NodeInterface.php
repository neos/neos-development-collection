<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model\Structure;

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
 * Interface of a Node
 *
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @author Robert Lemke <robert@typo3.org>
 */
interface NodeInterface {

	const CHILDNODESORDER_UNDEFINED = 0;
	const CHILDNODESORDER_ORDERED = 1;
	const CHILDNODESORDER_NAMED = 2;

	/**
	 * Adds a child node to the list of existing child nodes
	 *
	 * @param \F3\TYPO3\Domain\Model\Structure\NodeInterface $childNode The child node to add
	 * @param \F3\FLOW3\Locale\Locale $locale If specified, the child node is marked with that locale. If not specified, multilingual and international is assumed.
	 * @return void
	 */
	public function addChildNode(\F3\TYPO3\Domain\Model\Structure\NodeInterface $childNode, \F3\FLOW3\Locale\Locale $locale = NULL);

	/**
	 * Sets a child node to which can be refered by the specified name.
	 *
	 * @param string $name The child node's name
	 * @param \F3\TYPO3\Domain\Model\Structure\NodeInterface $childNode The child node
	 * @param \F3\FLOW3\Locale\Locale $locale If specified, the child node is marked with that locale. If not specified, multilingual and international is assumed.
	 * @return void
	 * @throws \F3\TYPO3\Domain\Exception\WrongNodeOrderMethod if the child node norder is already set and is not "NAMED"
	 * @throws \F3\TYPO3\Domain\Exception\NodeAlreadyExists if a child node with the specified name and locale already exists
	 */
	public function setNamedChildNode($name, \F3\TYPO3\Domain\Model\Structure\NodeInterface $childNode, \F3\FLOW3\Locale\Locale $locale = NULL);

	/**
	 * Returns the child notes of this node.
	 * Note that the child nodes are indexed by language and region!
	 *
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext The current content context for determining the locale of the nodes to return
	 * @return array Child nodes in the form of array('{language}' => array ('{region}' => {child nodes}))
	 */
	public function getChildNodes(\F3\TYPO3\Domain\Service\ContentContext $contentContext);

	/**
	 * Tells if this node has any child nodes
	 *
	 * @return boolean TRUE if the node has child nodes, otherwise FALSE
	 */
	public function hasChildNodes();

	/**
	 * Returns the order of the attached child nodes.
	 *
	 * If no child node has been added yet, the order is undefined. Otherwise the
	 * order is determined by the method how the first child node has been added.
	 *
	 * @return integer One of the CHILDNODEORDER_* constants
	 */
	public function getChildNodesOrder();
}

?>