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
 * A Workspace
 *
 * @package TYPO3CR
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_TYPO3CR_Workspace implements T3_phpCR_WorkspaceInterface {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var T3_TYPO3CR_Session
	 */
	protected $session;

	/**
	 * @var T3_FLOW3_Component_Manager
	 */
	protected $componentManager;

	/**
	 * @var T3_TYPO3CR_StorageAccess
	 */
	protected $storageAccess;

	/**
	 * Constructs a Workspace object
	 *
	 * @param string $name
	 * @param T3_FLOW3_Component_ManagerInterface $componentManager
	 * @param T3_phpCR_SessionInterface $session
	 * @param T3_phpCR_StorageAccessInterface $storageAccess
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($name, T3_phpCR_SessionInterface $session, T3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->name = (T3_PHP6_Functions::strlen($name) ? $name : 'default');
		$this->session = $session;
		$this->componentManager = $componentManager;
	}

	/**
	 * Returns the Session object through which this Workspace object was
	 * acquired.
	 * 
	 * @return T3_TYPO3CR_Session
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * Returns the name of the actual persistent workspace represented by
	 * this Workspace object. This is the name used in Repository->login().
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the NamespaceRegistry object, which is used to access the
	 * mapping between prefixes and namespaces. In level 2 repositories the
	 * NamespaceRegistry can also be used to change the namespace
	 * mappings.
	 *
	 * @return T3_TYPO3CR_NamespaceRegistry
	 * @throws T3_phpCR_RepositoryException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getNamespaceRegistry() {
		return $this->componentManager->getComponent('T3_phpCR_NamespaceRegistryInterface');
	}
}

?>