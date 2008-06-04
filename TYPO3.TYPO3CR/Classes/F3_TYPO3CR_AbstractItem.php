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
	 * @var F3_TYPO3CR_Storage_BackendInterface
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
	 * @return F3_PHPCR_ItemInterface The ancestor of this Item at the specified depth.
	 * @throws F3_PHPCR_ItemNotFoundException if depth &lt; 0 or depth &gt; n where n is the depth of this item.
	 * @throws F3_PHPCR_AccessDeniedException if the current session does not have sufficient access rights to retrieve the specified node.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
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
	 * Returns the depth of this Item in the workspace tree. Returns the depth
	 * below the root node of this Item (counting this Item itself).
	 *
	 * * The root node returns 0.
	 * * A property or child node of the root node returns 1.
	 * * A property or child node of a child node of the root returns 2.
	 * * And so on to this Item.
	 *
	 * This default implementation determines the depth by counting the
	 * slashes in the path returned by getPath().
	 *
	 * @return integer The depth of this Item in the workspace hierarchy.
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
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
	 * Returns the Session through which this Item was acquired. Every Item
	 * can ultimately be traced back through a series of method calls to the
	 * call Session->getRootNode(), Session->getItem() or
	 * Session->getNodeByIdentifier(). This method returns that Session object.
	 *
	 * @return F3_PHPCR_SessionInterface the Session through which this Item was acquired.
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * Returns true if this is a new item, meaning that it exists only in
	 * transient storage on the Session and has not yet been saved. Within a
	 * transaction, isNew on an Item may return false (because the item has
	 * been saved) even if that Item is not in persistent storage (because the
	 * transaction has not yet been committed).
	 *
	 * Note that if an item returns true on isNew, then by definition is parent
	 * will return true on isModified.
	 *
	 * @return boolean TRUE if this item is new; FALSE otherwise.
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 */
	public function isNew() {
		return $this->isNew;
	}

	/**
	 * Returns true if this Item has been saved but has subsequently been
	 * modified through the current session and therefore the state of this
	 * item as recorded in the session differs from the state of this item as
	 * saved. Within a transaction, isModified on an Item may return false
	 * (because the Item has been saved since the modification) even if the
	 * modification in question is not in persistent storage (because the
	 * transaction has not yet been committed).
	 *
	 * @return boolean TRUE if this item is modified; FALSE otherwise.
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 */
	public function isModified() {
		return $this->isModified;
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
	 * @param F3_PHPCR_ItemInterface $otherItem the Item object to be tested for identity with this Item.
	 * @return boolean TRUE if this Item object and otherItem represent the same actual repository item; FALSE otherwise.
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
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
	 * Accepts an ItemVistor. Calls the appropriate ItemVistor visit method of
	 * the visitor according to whether this Item is a Node or a Property.
	 *
	 * @param F3_PHPCR_ItemVisitorInterface $visitor The ItemVisitor to be accepted.
	 * @throws RepositoryException if an error occurs.
	 */
	public function accept(F3_PHPCR_ItemVisitorInterface $visitor) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212577699);
	}


	// non-JSR-283 methods below


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

}
?>