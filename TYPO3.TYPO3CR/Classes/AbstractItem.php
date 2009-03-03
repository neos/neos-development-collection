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
 * @package TYPO3CR
 * @version $Id$
 */

/**
 * An Item
 *
 * @package TYPO3CR
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var \F3\TYPO3CR\Node
	 */
	protected $parentNode;

	/**
	 * Returns the name of this item. The name is the last item in the path,
	 * minus any square-bracket index that may exist. If this item is the root
	 * node of the workspace (i.e., if this.getDepth() == 0), an empty string
	 * will be returned.
	 *
	 * @return string The name of the item
	 * @throws \F3\PHPCR\RepositoryException
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function getName() {
		if ($this->isNode() && $this->parentNode == NULL) {
			return '';
		}

		return $this->name;
	}

	/**
	 * Returns the ancestor of the specified depth.
	 *
	 * An ancestor of depth x is the Item that is x levels down along the path from
	 * the root node to this Item.
	 *
	 * * depth = 0 returns the root node.
	 * * depth = 1 returns the child of the root node along the path to this Item.
	 * * depth = 2 returns the grandchild of the root node along the path to this Item.
	 * * And so on to depth = n, where n is the depth of this Item, which returns this Item itself.
	 *
	 * If depth > n is specified then a ItemNotFoundException is thrown.
	 *
	 * This default implementation handles the root node at depth zero and
	 * this item at depth equal to the depth of this item as special cases,
	 * and uses Session->getItem(String) to retrieve other
	 * ancestors based on the ancestor path calculated from the path of this
	 * node as returned by Item->getPath().
	 *
	 * @param integer $depth An integer, 0 <= depth <= n where n is the depth of this Item.
	 * @return \F3\PHPCR\ItemInterface The ancestor of this Item at the specified depth.
	 * @throws \F3\PHPCR\ItemNotFoundException if depth &lt; 0 or depth &gt; n where n is the depth of this item.
	 * @throws \F3\PHPCR\AccessDeniedException if the current session does not have sufficient access rights to retrieve the specified node.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @author Ronny Unger <ru@php-workx.de>
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
			$slash = \F3\PHP6\Functions::strpos($path, '/', $slash+1);
			if ($slash === FALSE) {
				throw new \F3\PHPCR\ItemNotFoundException('Invalid ancestor depth (' . $depth . ')', 1187530839);
			}
		}
		$slash = \F3\PHP6\Functions::strpos($path, '/', $slash+1);
		if ($slash == -1) {
			return $this;
		}

		try {
			return $this->getSession()->getItem(\F3\PHP6\Functions::substr($path, 0, $slash));
		} catch (\F3\PHPCR\ItemNotFoundException $e) {
			throw new \F3\PHPCR\AccessDeniedException('Ancestor access denied (' . $depth . ')', 1187530845);
		}
	}

	/**
	 * Returns the depth of this Item in the workspace tree. Returns the depth
	 * below the root node of this Item (counting this Item itself).
	 *
	 * * The root node returns 0.
	 * * A property or child node of the root node returns 1.
	 * * A property or child node of a child node of the root returns 2.
	 * * And so on to this Item.
	 *
	 * @return integer The depth of this Item in the workspace hierarchy.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
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
	 * Returns the Session through which this Item was acquired. Every Item
	 * can ultimately be traced back through a series of method calls to the
	 * call Session->getRootNode(), Session->getItem() or
	 * Session->getNodeByIdentifier(). This method returns that Session object.
	 *
	 * @return \F3\PHPCR\SessionInterface the Session through which this Item was acquired.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 * @author Ronny Unger <ru@php-workx.de>
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
	 * * If they are Property objects they have identical names and
	 *   isSame is true of their parent nodes.
	 *
	 * This method does not compare the states of the two items. For example, if two
	 * Item objects representing the same actual workspace item have been
	 * retrieved through two different sessions and one has been modified, then this method
	 * will still return true when comparing these two objects. Note that if two
	 * Item objects representing the same workspace item
	 * are retrieved through the same session they will always reflect the
	 * same state (see section 5.1.3 Reflecting Item State in the JSR 283 specification
	 * document) so comparing state is not an issue.
	 *
	 * @param \F3\PHPCR\ItemInterface $otherItem the Item object to be tested for identity with this Item.
	 * @return boolean TRUE if this Item object and otherItem represent the same actual repository item; FALSE otherwise.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Add (proper) checks for the repository and workspace conditions
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
	 * Accepts an ItemVistor. Calls the appropriate ItemVistor visit method of
	 * the visitor according to whether this Item is a Node or a Property.
	 *
	 * @param \F3\PHPCR\ItemVisitorInterface $visitor The ItemVisitor to be accepted.
	 * @throws RepositoryException if an error occurs.
	 */
	public function accept(\F3\PHPCR\ItemVisitorInterface $visitor) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212577699);
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