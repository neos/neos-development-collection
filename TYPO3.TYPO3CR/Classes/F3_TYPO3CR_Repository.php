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
 * A Repository
 *
 * @package TYPO3CR
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Repository implements F3_PHPCR_RepositoryInterface {

	/**
	 * @var F3_FLOW3_Configuration_Container
	 */
	protected $settings;

	/**
	 * @var F3_FLOW3_Component_FactoryInterface
	 */
	protected $componentFactory;

	/**
	 * @var F3_TYPO3CR_Storage_BackendInterface
	 */
	protected $storageAccess;

	/**
	 * An array with all descriptor keys and their values (yes, all strings!)
	 * @var array
	 */
	protected $descriptors = array(
		self::LEVEL_1_SUPPORTED => 'false',
		self::LEVEL_2_SUPPORTED => 'false',
		self::OPTION_ACTIVITIES_SUPPORTED => 'false',
		self::OPTION_BASELINES_SUPPORTED => 'false',
		self::OPTION_RETENTION_AND_HOLD_SUPPORTED => 'false',
		self::OPTION_JOURNALED_OBSERVATION_SUPPORTED => 'false',
		self::OPTION_LIFECYCLE_SUPPORTED => 'false',
		self::OPTION_LOCKING_SUPPORTED => 'false',
		self::OPTION_NODE_TYPE_REG_SUPPORTED => 'false',
		self::OPTION_OBSERVATION_SUPPORTED => 'false',
		self::OPTION_QUERY_SQL_SUPPORTED => 'false',
		self::OPTION_ACCESS_CONTROL_SUPPORTED => 'false',
		self::OPTION_SIMPLE_VERSIONING_SUPPORTED => 'false',
		self::OPTION_TRANSACTIONS_SUPPORTED => 'false',
		self::OPTION_VERSIONING_SUPPORTED => 'false',
		self::REP_NAME_DESC => 'TYPO3 CR',
		self::REP_VENDOR_DESC => 'TYPO3 Association',
		self::REP_VENDOR_URL_DESC => 'http://association.typo3.org/',
		self::REP_VERSION_DESC => '0.0.0',
		self::SPEC_NAME_DESC => 'Content Repository for Java Technology API',
		self::SPEC_VERSION_DESC => '2.0'
	);

	/**
	 * Constructs a Repository object.
	 *
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_FactoryInterface $componentFactory) {
		$this->componentFactory = $componentFactory;
		$this->settings = $this->componentFactory->getComponent('F3_FLOW3_Configuration_Manager')->getSettings('TYPO3CR');
		$this->storageAccess = $this->componentFactory->getComponent($this->settings->storage->backend, $this->settings->storage->backendOptions);
	}

	/**
	 * Authenticates the user using the supplied credentials. If workspaceName is recognized as the
	 * name of an existing workspace in the repository and authorization to access that workspace
	 * is granted, then a new Session object is returned. The format of the string workspaceName
	 * depends upon the implementation.
	 * If credentials is null, it is assumed that authentication is handled by a mechanism external
	 * to the repository itself and that the repository implementation exists within a context
	 * (for example, an application server) that allows it to handle authorization of the request
	 * for access to the specified workspace.
	 *
	 * If workspaceName is null, a default workspace is automatically selected by the repository
	 * implementation. This may, for example, be the "home workspace" of the user whose credentials
	 * were passed, though this is entirely up to the configuration and implementation of the
	 * repository. Alternatively, it may be a "null workspace" that serves only to provide the
	 * method Workspace.getAccessibleWorkspaceNames(), allowing the client to select from among
	 * available "real" workspaces.
	 *
	 * @param F3_PHPCR_Credentials $credentials The credentials of the user
	 * @param string $workspaceName the name of a workspace
	 * @return F3_TYPO3CR_Session a valid session for the user to access the repository
	 * @throws F3_PHPCR_LoginException If the login fails
	 * @throws F3_PHPCR_NoSuchWorkspacexception If the specified workspaceName is not recognized
	 * @throws F3_PHPCR_RepositoryException if another error occurs
	 * @todo Currently given credentials are not checked at all!
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function login($credentials = NULL, $workspaceName = 'default') {
		if ($credentials !== NULL && !($credentials instanceof F3_PHPCR_CredentialsInterface)) throw new F3_PHPCR_RepositoryException('$credentials must be an instance of F3_PHPCR_CredentialsInterface', 1181042933);
		if ($workspaceName !== 'default') {
			throw new F3_PHPCR_NoSuchWorkspaceException('Only default workspace supported for now', 1181063009);
		}
		$this->storageAccess->connect();
		return $this->componentFactory->getComponent('F3_PHPCR_SessionInterface', $workspaceName, $this, $this->storageAccess);
	}

	/**
	 * Returns a string array holding all descriptor keys available for this
	 * implementation. This set must contain at least the built-in keys
	 * defined by the string constants in this interface. Used in conjunction
	 * with getDescriptor(String name) to query information about this
	 * repository implementation.
	 *
	 * @return array a string array holding all descriptor keys
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDescriptorKeys() {
		return array_keys($this->descriptors);
	}

	/**
	 * Returns the descriptor for the specified key. Used to query information
	 * about this repository implementation. The set of available keys can be
	 * found by calling getDescriptorKeys(). If the specified key is not found,
	 * null is returned.
	 *
	 * @param string $key a string corresponding to a descriptor for this repository implementation.
	 * @return string a descriptor string or NULL if not found
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDescriptor($key) {
		return (isset($this->descriptors[$key]) ? $this->descriptors[$key] : NULL);
	}

}

?>