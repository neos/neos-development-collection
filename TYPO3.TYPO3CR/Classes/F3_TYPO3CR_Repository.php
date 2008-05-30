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

	const REP_VENDOR_DESC = 'TYPO3 Association';
	const REP_VENDOR_URL_DESC = 'http://association.typo3.org/';
	const REP_NAME_DESC = 'TYPO3 CR';
	const REP_VERSION_DESC = '0.0.0';
	const LEVEL_1_SUPPORTED = FALSE;
	const LEVEL_2_SUPPORTED = FALSE;
	const OPTION_NODE_TYPE_REG_SUPPORTED = FALSE;
	const OPTION_TRANSACTIONS_SUPPORTED = FALSE;
	const OPTION_VERSIONING_SUPPORTED = FALSE;
	const OPTION_ACTIVITIES_SUPPORTED = FALSE;
	const OPTION_BASELINES_SUPPORTED = FALSE;
	const OPTION_OBSERVATION_SUPPORTED = FALSE;
	const OPTION_SYNC_OBSERVATION_SUPPORTED = FALSE;
	const OPTION_LOCKING_SUPPORTED = FALSE;
	const OPTION_QUERY_SQL_SUPPORTED = FALSE;
	const OPTION_AC_DISCOVERY_SUPPORTED = FALSE;
	const OPTION_AC_MANAGEMENT_SUPPORTED = FALSE;
	const OPTION_LIFECYCLE_SUPPORTED = FALSE;
	const QUERY_XPATH_POS_INDEX = FALSE;
	const QUERY_XPATH_DOC_ORDER = FALSE;

	/**
	 * @var F3_FLOW3_Component_Manager
	 */
	protected $componentManager;

	/**
	 * @var F3_TYPO3CR_Storage_BackendInterface
	 */
	protected $storageAccess;

	/**
	 * @var An array with all descriptor key and their value, coming from the associated class constant.
	 */
	protected $descriptors = array(
		'SPEC_VERSION_DESC' => self::SPEC_VERSION_DESC,
		'SPEC_NAME_DESC' => self::SPEC_NAME_DESC,
		'REP_VENDOR_DESC' => self::REP_VENDOR_DESC,
		'REP_VENDOR_URL_DESC' => self::REP_VENDOR_URL_DESC,
		'REP_NAME_DESC' => self::REP_NAME_DESC,
		'REP_VERSION_DESC' => self::REP_VERSION_DESC,
		'LEVEL_1_SUPPORTED' => self::LEVEL_1_SUPPORTED,
		'LEVEL_2_SUPPORTED' => self::LEVEL_2_SUPPORTED,
		'OPTION_NODE_TYPE_REG_SUPPORTED' => self::OPTION_NODE_TYPE_REG_SUPPORTED,
		'OPTION_TRANSACTIONS_SUPPORTED' => self::OPTION_TRANSACTIONS_SUPPORTED,
		'OPTION_VERSIONING_SUPPORTED' => self::OPTION_VERSIONING_SUPPORTED,
		'OPTION_ACTIVITIES_SUPPORTED' => self::OPTION_ACTIVITIES_SUPPORTED,
		'OPTION_BASELINES_SUPPORTED' => self::OPTION_BASELINES_SUPPORTED,
		'OPTION_OBSERVATION_SUPPORTED' => self::OPTION_OBSERVATION_SUPPORTED,
		'OPTION_SYNC_OBSERVATION_SUPPORTED' => self::OPTION_SYNC_OBSERVATION_SUPPORTED,
		'OPTION_LOCKING_SUPPORTED' => self::OPTION_LOCKING_SUPPORTED,
		'OPTION_QUERY_SQL_SUPPORTED' => self::OPTION_QUERY_SQL_SUPPORTED,
		'OPTION_AC_DISCOVERY_SUPPORTED' => self::OPTION_AC_DISCOVERY_SUPPORTED,
		'OPTION_AC_MANAGEMENT_SUPPORTED' => self::OPTION_AC_MANAGEMENT_SUPPORTED,
		'OPTION_LIFECYCLE_SUPPORTED' => self::OPTION_LIFECYCLE_SUPPORTED,
		'QUERY_XPATH_POS_INDEX' => self::QUERY_XPATH_POS_INDEX,
		'QUERY_XPATH_DOC_ORDER' => self::QUERY_XPATH_DOC_ORDER
	);

	/**
	 * Constructs a Repository object.
	 *
	 * @param F3_FLOW3_Component_Manager $componentManager
	 * @param F3_TYPO3CR_Storage_BackendInterface $storageAccess
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_ManagerInterface $componentManager, F3_TYPO3CR_Storage_BackendInterface $storageAccess) {
		$this->componentManager = $componentManager;
		$this->storageAccess = $storageAccess;

	}

	/**
	 * Authenticates the user using the supplied credentials. If workspaceName is
	 * recognized as the name of an existing workspace in the repository and
	 * authorization to access that workspace is granted, then a new Session
	 * object is returned.
	 *
	 * @param  F3_PHPCR_Credentials $credentials
	 * @param  string $workspaceName
	 * @return F3_TYPO3CR_Session
	 * @throws F3_PHPCR_RepositoryException
	 * @throws F3_PHPCR_NoSuchWorkspacexception
	 * @todo   Currently given credentials are not checked at all!
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function login($credentials = NULL, $workspaceName = 'default') {
		if ($credentials !== NULL && !($credentials instanceof F3_PHPCR_CredentialsInterface)) throw new F3_PHPCR_RepositoryException('$credentials must be an instance of F3_PHPCR_Credentials', 1181042933);
		if ($workspaceName !== 'default') {
			throw new F3_PHPCR_NoSuchWorkspaceException('Only default workspace supported for now', 1181063009);
		}

		$this->session = $this->componentManager->getComponent('F3_PHPCR_SessionInterface', $workspaceName, $this, $this->storageAccess);
		return $this->session;
	}

	/**
	 * Returns a string array holding all descriptor keys available for this
	 * implementation. Used in conjunction with getDescriptor(String name)
	 * to query information about this repository implementation.
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDescriptorKeys() {
		return array_keys($this->descriptors);
	}

	/**
	 * Returns the descriptor for the specified key. Used to query information about
	 * this repository implementation. The set of available keys can be found by
	 * calling getDescriptorKeys. If the specified key is not found, NULL is
	 * returned.
	 *
	 * @return mixed
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDescriptor($key) {
		return (isset($this->descriptors[$key]) ? $this->descriptors[$key] : NULL);
	}

}

?>