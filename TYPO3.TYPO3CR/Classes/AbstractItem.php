<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR;

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
 * An Item
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
abstract class AbstractItem implements \F3\PHPCR\ItemInterface {

	/**
	 * Pattern to match valid local JCR names
	 */
	const PATTERN_NAME =
		"!\A(?:
			[^./:[*| \]\t\r\n] # onechar
			|
			\.[^./:[*| \]\t\r\n] # twochar
			|
			[^./:[*| \]\t\r\n]\. # twochar
			|
			[^./:[*| \]\t\r\n]{2,2} # twochar
			|
			[^/:[*| \]\t\r\n][^/:[*|\]\t\r\n]+[^/:[*| \]\t\r\n] # multichar
		)\Z!Sux";

	/**
	 * @var \F3\TYPO3CR\Session
	 */
	protected $session;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var \F3\TYPO3CR\Node
	 */
	protected $parentNode;

	/**
	 * Returns the name of this Item in qualified form. If this Item is the root
	 * node of the workspace, an empty string is returned.
	 *
	 * @return string the name of this Item in qualified form or an empty string if this Item is the root node of a workspace.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 * @author Ronny Unger <ru@php-workx.de>
	 * @api
	 */
	public function getName() {
		if ($this->isNode() && $this->parentNode == NULL) {
			return '';
		}

		return $this->name;
	}

	/**
	 * Returns the ancestor of this Item at the specified depth. An ancestor of
	 * depth x is the Item that is x levels down along the path from the root
	 * node to this Item.
	 *
	 * * depth = 0 returns the root node of a workspace.
	 * * depth = 1 returns the child of the root node along the path to this Item.
	 * * depth = 2 returns the grandchild of the root node along the path to this Item.
	 * * And so on to depth = n, where n is the depth of this Item, which returns this Item itself.
	 *
	 * If this node has more than one path (i.e., if it is a descendant of a
	 * shared node) then the path used to define the ancestor is implementaion-
	 * dependent.
	 *
	 * @param integer $depth An integer, 0 <= depth <= n where n is the depth of this Item.
	 * @return \F3\PHPCR\ItemInterface The ancestor of this Item at the specified depth.
	 * @throws \F3\PHPCR\ItemNotFoundException if depth &lt; 0 or depth &gt; n where n is the depth of this item.
	 * @throws \F3\PHPCR\AccessDeniedException if the current session does not have sufficient access to retrieve the specified node.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Ronny Unger <ru@php-workx.de>
	 * @api
	 */
	public function getAncestor($depth) {
		if ($depth < 0 || $depth > $this->getDepth()) {
			throw new \F3\PHPCR\ItemNotFoundException('Invalid ancestor depth (' . $depth . ')', 1187530802);
		}

		if ($depth == 0) {
			return $this->getSession()->getRootNode();
		}

		$path = $this->getPath();
		$slash = 0;
		for ($i = 0; $i < $depth-1; $i++) {
			$slash = \F3\FLOW3\Utility\Unicode\Functions::strpos($path, '/', $slash+1);
			if ($slash === FALSE) {
				throw new \F3\PHPCR\ItemNotFoundException('Invalid ancestor depth (' . $depth . ')', 1187530839);
			}
		}
		$slash = \F3\FLOW3\Utility\Unicode\Functions::strpos($path, '/', $slash+1);
		if ($slash == -1) {
			return $this;
		}

		try {
			return $this->getSession()->getItem(\F3\FLOW3\Utility\Unicode\Functions::substr($path, 0, $slash));
		} catch (\F3\PHPCR\ItemNotFoundException $e) {
			throw new \F3\PHPCR\AccessDeniedException('Ancestor access denied (' . $depth . ')', 1187530845);
		}
	}

	/**
	 * Returns the depth of this Item in the workspace item graph.
	 *
	 * * The root node returns 0.
	 * * A property or child node of the root node returns 1.
	 * * A property or child node of a child node of the root returns 2.
	 * * And so on to this Item.
	 *
	 * @return integer The depth of this Item in the workspace item graph.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getDepth() {
		$path = $this->getPath();
		if ($path === '/') {
			return 0;
		} else {
			return substr_count($path, '/');
		}
	}

	/**
	 * Returns the Session through which this Item was acquired.
	 *
	 * @return \F3\PHPCR\SessionInterface the Session through which this Item was acquired.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 * @author Ronny Unger <ru@php-workx.de>
	 * @api
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * Returns TRUE if this Item object represents the same actual workspace
	 * item as the object otherItem.
	 *
	 * Two Item objects represent the same workspace item if all the following
	 * are true:
	 *
	 * * Both objects were acquired through Session objects that were created
	 *   by the same Repository object.
	 * * Both objects were acquired through Session objects bound to the same
	 *   repository workspace.
	 * * The objects are either both Node objects or both Property
	 *   objects.
	 * * If they are Node objects, they have the same identifier.
	 * * If they are Property objects they have identical names and
	 *   isSame() is TRUE of their parent nodes.
	 *
	 * This method does not compare the states of the two items. For example, if
	 * two Item objects representing the same actual workspace item have been
	 * retrieved through two different sessions and one has been modified, then
	 * this method will still return true when comparing these two objects.
	 * Note that if two Item objects representing the same workspace item are
	 * retrieved through the same session they will always reflect the same
	 * state.
	 *
	 * @param \F3\PHPCR\ItemInterface $otherItem the Item object to be tested for identity with this Item.
	 * @return boolean TRUE if this Item object and otherItem represent the same actual repository item; FALSE otherwise.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Add (proper) checks for the repository and workspace conditions
	 * @api
	 */
	public function isSame(\F3\PHPCR\ItemInterface $otherItem) {
		if ($this->getSession()->getWorkspace()->getName() != $otherItem->getSession()->getWorkspace()->getName()) return FALSE;

		if ($this instanceof \F3\PHPCR\NodeInterface) {
			return (
				($otherItem instanceof \F3\PHPCR\NodeInterface) &&
				($this->getIdentifier() == $otherItem->getIdentifier())
			);
		} elseif ($otherItem instanceof \F3\PHPCR\PropertyInterface) {
			return (
				($otherItem instanceof \F3\PHPCR\PropertyInterface) &&
				($this->getName() == $otherItem->getName()) &&
				$this->getParent()->isSame($otherItem->getParent())
			);
		}

		return FALSE;
	}

	/**
	 * Accepts an ItemVisitor. Calls the appropriate ItemVisitor visit method of
	 * the visitor according to whether this Item is a Node or a Property.
	 *
	 * @param \F3\PHPCR\ItemVisitorInterface $visitor The ItemVisitor to be accepted.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 * @api
	 */
	public function accept(\F3\PHPCR\ItemVisitorInterface $visitor) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212577699);
	}

	/**
	 * If keepChanges is false, this method discards all pending changes
	 * currently recorded in this Session that apply to this Item or any
	 * of its descendants (that is, the subgraph rooted at this Item) and
	 * returns all items to reflect the current saved state. Outside a
	 * transaction this state is simple the current state of persistent
	 * storage. Within a transaction, this state will reflect persistent
	 * storage as modified by changes that have been saved but not yet
	 * committed.
	 * If keepChanges is true then pending change are not discarded but
	 * items that do not have changes pending have their state refreshed
	 * to reflect the current saved state, thus revealing changes made by
	 * other sessions.
	 *
	 * @param boolean $keepChanges a boolean
	 * @return void
	 * @throws \F3\PHPCR\InvalidItemStateException if this Item object represents a workspace item that has been removed (either by this session or another).
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @api
	*/
	public function refresh($keepChanges) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212577830);
	}


	// non-JSR-283 methods


	/**
	 * Returns true of the given name is a valid JCR name.
	 *
	 * @param string $name
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo add support for extended names
	 * @todo check namespace if given!?
	 */
	public function isValidName($name) {
		$prefix = '';

		if ($name == '') return FALSE;

		if ($name[0] === '{') {
			throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Extended names are not yet supported, sorry', 1225814871);
		} elseif (strpos($name, ':') !== FALSE) {
			list($prefix, $name) = explode(':', $name, 2);
		}
		return preg_match(self::PATTERN_NAME, $name);
	}

}
?>