<?php
declare(ENCODING = 'utf-8');

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
 * @version $Id$
 */

/**
 * An Item
 *
 * @package TYPO3CR
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
abstract class F3_TYPO3CR_AbstractItem implements F3_PHPCR_ItemInterface {

	/**
	 * @var F3_TYPO3CR_StorageAccess
	 */
	protected $storageAccess;

	/**
	 * @var F3_TYPO3CR_Session
	 */
	protected $session;

	/**
	 * @var F3_FLOW3_Component_Manager
	 */
	protected $componentManager;

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var F3_TYPO3CR_Node
	 */
	protected $parentNode;

	/**
	 * @var boolean
	 */
	protected $isNew;

	/**
	 * @var boolean
	 */
	protected $isModified;

	/**
	 * @var boolean
	 */
	protected $isRemoved;

	/**
	 * Returns the session associated with this item.
	 *
	 * @return F3_PHPCR_Session
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * Returns the name of this item. The name is the last item in the path,
	 * minus any square-bracket index that may exist. If this item is the root
	 * node of the workspace (i.e., if this.getDepth() == 0), an empty string
	 * will be returned.
	 *
	 * @return string The name of the item
	 * @throws F3_PHPCR_RepositoryException
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function getName() {
		if ($this->isNode() && $this->parentNode == NULL) {
			return '';
		}

		return $this->name;
	}

	/**
	 * Returns the new flag of Item
	 *
	 * @return boolean
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 */
	public function isNew() {
		return $this->isNew;
	}

	/**
	 * Returns the modified flag of Item
	 *
	 * @return boolean
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 */
	public function isModified() {
		return $this->isModified;
	}

	/**
	 * Returns the deleted flag of Item
	 *
	 * @return boolean
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 */
	public function isRemoved() {
		return $this->isRemoved;
	}

	/**
	 * Set the new flag of Item
	 *
	 * @param boolean $isNew The new state to set
	 * @return void
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 */
	public function setNew($isNew) {
		$this->isNew = (boolean)$isNew;
	}

	/**
	 * Set the modified flag of Item
	 *
	 * @param boolean $isModified The modified state to set
	 * @return void
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 */
	public function setModified($isModified) {
		$this->isModified=(boolean)$isModified;
	}

	/**
	 * Set the deleted flag of Item
	 *
	 * @param boolean $isRemoved The removed state to set
	 * @return void
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 */
	public function setRemoved($isRemoved) {

		$this->isRemoved=(boolean)$isRemoved;
	}

	/**
	 * Returns TRUE if this Item is a Node; returns FALSE if this Item is a
	 * Property.
	 *
	 * @return boolean
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public abstract function isNode();

	/**
	 * Returns the absolute path to this item.
	 *
	 * If the path includes items that are same name sibling nodes or multi-
	 * value properties then those elements in the path will include the
	 * appropriate “square bracket” index notation (for example, /a/b[3]/c).
	 *
	 * @return string The path to the Item
	 * @throws F3_PHPCR_RepositoryException
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public abstract function getPath();

	/**
	 * Returns the parent of this Item.
	 *
	 * An ItemNotFoundException is thrown if there is no parent node. This
	 * only happens if this item is the root node of a workspace.
	 *
	 * An AccessDeniedException is thrown if the current session does not
	 * have sufficient access permissions to retrieve the parent of this item.
	 *
	 * A RepositoryException is thrown if another error occurs.
	 *
	 * @return F3_PHPCR_Node The parent node of the item
	 * @throws F3_PHPCR_ItemNotFoundException
	 * @throws F3_PHPCR_AccessDeniedException
	 * @throws F3_PHPCR_RepositoryException
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public abstract function getParent();

	/**
	 * Returns the ancestor of this item at the given depth.
	 *
	 * The default implementation handles the root node at depth zero and
	 * this item at depth equal to the depth of this item as special cases,
	 * and uses Session->getItem(String) to retrieve other
	 * ancestors based on the ancestor path calculated from the path of this
	 * node as returned by Item->getPath().
	 *
	 * @param integer $depth Depth of the returned ancestor item
	 * @return F3_PHPCR_Item Ancestor item
	 * @throws F3_PHPCR_ItemNotFoundException if the given depth is negative or greater than the depth of this item
	 * @throws F3_PHPCR_AccessDeniedException if access to the ancestor item is denied
	 * @throws F3_PHPCR_RepositoryException if an error occurs
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function getAncestor($depth) {
		if ($depth < 0 || $depth > $this->getDepth()) {
			throw new F3_PHPCR_ItemNotFoundException('Invalid ancestor depth (' . $depth . ')', 1187530802);
		}

		if ($depth == 0) {
			return $this->getSession()->getRootNode();
		}

		$path = $this->getPath();
		$slash = 0;
		for ($i = 0; $i < $depth-1; $i++) {
			$slash = F3_PHP6_Functions::strpos($path, '/', $slash+1);
			if ($slash === FALSE) {
				throw new F3_PHPCR_ItemNotFoundException('Invalid ancestor depth (' . $depth . ')', 1187530839);
			}
		}
		$slash = F3_PHP6_Functions::strpos($path, '/', $slash+1);
		if ($slash == -1) {
			return $this;
		}

		try {
			return $this->getSession()->getItem(F3_PHP6_Functions::substr($path, 0, $slash));
		} catch (F3_PHPCR_ItemNotFoundException $e) {
			throw new F3_PHPCR_AccessDeniedException('Ancestor access denied (' . $depth . ')', 1187530845);
		}
	}

	/**
	 * Returns the depth of this item.
	 *
	 * The default implementation determines the depth by counting the
	 * slashes in the path returned by getPath().
	 *
	 * @return integer The depth of this item
	 * @throws F3_PHPCR_RepositoryException
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function getDepth() {
		$path = $this->getPath();
		if ($path == '/') {
			return 0;
		} else {
			$depth = 1;
			$slash = F3_PHP6_Functions::strpos($path, '/', 1);
			while ($slash !== FALSE) {
				$depth++;
				$slash = F3_PHP6_Functions::strpos($path, '/', $slash+1);
			}
			return $depth;
		}
	}

	/**
	 * Returns TRUE if this Item object represents the same actual workspace
	 * item as the object otherItem. Two Item objects represent the same
	 * workspace item if all the following are TRUE:
	 * - Both objects were acquired through Session objects that were created by
	 * 	 the same Repository object.
	 * - Both objects were acquired through Session objects bound to the same
	 * 	 repository workspace.
	 * - The objects are either both Node objects or both Property objects.
	 * - If they are Node objects, they have the same correspondence identifier.
	 * 	 Note that this is the identifier used to determine whether two nodes
	 * 	 in different workspaces correspond (see 3.10.2 Multiple Workspaces and
	 * 	 Corresponding Nodes) but obviously it is also true that any node has the
	 * 	 same correspondence identifier as itself. Hence, this identifier is used
	 * 	 here to determine whether two different Java Node objects actually
	 * 	 represent the same workspace node.
	 * - If they are Property objects they have identical names and isSame is
	 * 	 true of their parent nodes.
	 *
	 * This method does not compare the states of the two items. For example, if
	 * two Item objects representing the same actual workspace item have been
	 * retrieved through two different sessions and one has been modified, then
	 * this method will still return FALSE when comparing these two objects. Note
	 * that if two Item objects representing the same workspace item are retrieved
	 * through the same session they will always reflect the same state
	 * (see 5.1.3 Reflecting Item State) so comparing state is not an issue.
	 *
	 * @param F3_PHPCR_ItemInterface $otherItem The item to which the comparison should be done
	 * @return boolean
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Add (proper) checks for the repository and workspace conditions
	 */
	public function isSame(F3_PHPCR_ItemInterface $otherItem) {
		if ($this->getSession()->getWorkspace()->getName() != $otherItem->getSession()->getWorkspace()->getName()) return FALSE;

		if ($this instanceof F3_TYPO3CR_Node) {
			return (
				($otherItem instanceof F3_TYPO3CR_Node) &&
				($this->getIdentifier() == $otherItem->getIdentifier())
			);
		} elseif ($otherItem instanceof F3_TYPO3CR_Property) {
			return (
				($otherItem instanceof F3_TYPO3CR_Property) &&
				($this->getName() == $otherItem->getName()) &&
				$this->getParent()->isSame($otherItem->getParent())
			);
		}

		return FALSE;
	}

	/**
	 * Delete the item
	 *
	 * @return void
	 */
	public abstract function remove();
}
?>