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
 * @subpackage NodeType
 * @version $Id$
 */

/**
 * A NodeTypeDefinition
 *
 * @package TYPO3CR
 * @subpackage NodeType
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_NodeType_NodeTypeDefinition implements F3_PHPCR_NodeType_NodeTypeDefinitionInterface {

	/**
	 * @var F3_FLOW3_Component_Manager
	 */
	protected $componentManager;

	/**
	 * @var F3_TYPO3CR_StorageAccess_StorageAccessInterface
	 */
	protected $storageAccess;

	/**
	 * @var integer
	 */
	protected $nodeTypeId;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * Constructs a NodeTypeDefinition
	 *
	 * @param integer $nodeTypeId The internal id of the nodetype
	 * @param F3_TYPO3CR_StorageAccess_StorageAccessInterface $storageAccess
	 * @param F3_FLOW3_Component_Manager $componentManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Check node type handling
	 */
	public function __construct($nodeTypeId, F3_TYPO3CR_StorageAccess_StorageAccessInterface $storageAccess, F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->nodeTypeId = $nodeTypeId;
		$this->componentManager = $componentManager;
		$this->storageAccess = $storageAccess;

		$rawNodeTypeData = $this->storageAccess->getRawNodeTypeById($this->nodeTypeId);
		$this->name = $rawNodeTypeData['name'];
	}

	/**
	 * Returns the name of the node type.
	 *
	 * In implementations that support node type registration (see 6.6 Node
	 * Type Registration), if this NodeTypeDefinition object is actually a
	 * newly-created empty NodeTypeTemplate, then this method will return
	 * NULL.
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the node type ID
	 *
	 * @return integer ID of node type
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 */
	public function getId() {
		return $this->nodeTypeId;
	}
}

?>