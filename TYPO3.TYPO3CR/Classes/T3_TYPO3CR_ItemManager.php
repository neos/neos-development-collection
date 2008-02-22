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
 * @version $Id: T3_TYPO3CR_ItemManager.php 328 2007-09-04 13:44:34Z robert $
 */

/**
 * ItemManager holds the new and modified nodes with Sessionscope
 *
 * @package TYPO3CR
 * @version $Id: T3_TYPO3CR_ItemManager.php 328 2007-09-04 13:44:34Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_TYPO3CR_ItemManager implements T3_TYPO3CR_ItemManagerInterface {

	/**
	 * @var T3_FLOW3_Component_Manager
	 */
	protected $componentManager;

	/**
	 * @var array Nodes
	 */
	protected $nodes;

	/**
	 * Constructs a Node
	 *
	 * @param T3_FLOW3_Component_Manager $componentManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(T3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
		$this->nodes = array();
	}

	/**
	 * Create the Node in nodes.
	 *
	 * @param T3_phpCR_NodeInterface		the node for add
	 * @return void
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 */
	public function addNode(T3_phpCR_NodeInterface $node) {
		$this->nodes[$node->getUUID()] = $node;
	}

	/**
	 * get nodes as a NodeIterator
	 *
	 * @return T3_phpCR_NodeIteratorInterface the nodes
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 * @todo How to remove an Item?
	 */
	public function getNodes() {

		if (count($this->nodes)===0) {
			return NULL;
		}

		foreach ($this->nodes as $node) {
			if (($node->isModified() === FALSE) && ($node->isNew() === FALSE)) unset($this->nodes[$node->getUUID()]);
		}

		return $this->nodes;
	}

}
?>