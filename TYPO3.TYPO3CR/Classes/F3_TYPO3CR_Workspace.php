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
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Workspace implements F3_PHPCR_WorkspaceInterface {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var F3_TYPO3CR_Session
	 */
	protected $session;

	/**
	 * @var F3_FLOW3_Component_Manager
	 */
	protected $componentManager;

	/**
	 * @var F3_TYPO3CR_StorageAccess
	 */
	protected $storageAccess;

	/**
	 * Constructs a Workspace object
	 *
	 * @param string $name
	 * @param F3_PHPCR_SessionInterface $session
	 * @param F3_PHPCR_StorageAccessInterface $storageAccess
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager
	 * 	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($name, F3_PHPCR_SessionInterface $session, F3_TYPO3CR_StorageAccessInterface $storageAccess, F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->name = (F3_PHP6_Functions::strlen($name) ? $name : 'default');
		$this->session = $session;
		$this->storageAccess = $storageAccess;
		$this->componentManager = $componentManager;
	}

	/**
	 * Returns the Session object through which this Workspace object was
	 * acquired.
	 *
	 * @return F3_TYPO3CR_Session
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
	 * @return F3_TYPO3CR_NamespaceRegistry
	 * @throws F3_PHPCR_RepositoryException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getNamespaceRegistry() {
		return $this->componentManager->getComponent('F3_PHPCR_NamespaceRegistryInterface', $this->storageAccess, $this->componentManager);
	}
}

?>