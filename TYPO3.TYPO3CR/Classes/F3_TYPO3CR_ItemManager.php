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
 * ItemManager holds the new and modified nodes with Sessionscope
 *
 * @package TYPO3CR
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_ItemManager implements F3_TYPO3CR_ItemManagerInterface {

	/**
	 * @var F3_FLOW3_Component_ManagerInterface
	 */
	protected $componentManager;

	/**
	 * @var array Nodes
	 */
	protected $nodes;

	/**
	 * Constructs a Node
	 *
	 * @param F3_FLOW3_Component_Manager $componentManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
		$this->nodes = array();
	}

	/**
	 * Create the Node in nodes.
	 *
	 * @param F3_PHPCR_NodeInterface		the node for add
	 * @return void
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 */
	public function addNode(F3_PHPCR_NodeInterface $node) {
		$this->nodes[$node->getIdentifier()] = $node;
	}

	/**
	 * get nodes as a NodeIterator
	 *
	 * @return F3_PHPCR_NodeIteratorInterface the nodes
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 * @todo How to remove an Item?
	 */
	public function getNodes() {

		if (count($this->nodes)===0) {
			return NULL;
		}

		foreach ($this->nodes as $node) {
			if (($node->isModified() === FALSE) && ($node->isNew() === FALSE)) unset($this->nodes[$node->getIdentifier()]);
		}

		return $this->nodes;
	}

}
?>