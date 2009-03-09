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
 * A Repository
 *
 * @package TYPO3CR
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Repository implements \F3\PHPCR\RepositoryInterface {

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\TYPO3CR\Storage\BackendInterface
	 */
	protected $storageBackend;

	/**
	 * An array with all standard descriptor keys and their values
	 * @var array
	 */
	protected $standardDescriptors = array(
		self::SPEC_VERSION_DESC => '2.0',
		self::SPEC_NAME_DESC => 'Content Repository for Java Technology API',
		self::REP_VENDOR_DESC => 'TYPO3 Association',
		self::REP_VENDOR_URL_DESC => 'http://association.typo3.org/',
		self::REP_NAME_DESC => 'TYPO3 CR',
		self::REP_VERSION_DESC => '0.0.0',
		self::WRITE_SUPPORTED => TRUE,
		self::IDENTIFIER_STABILITY => self::IDENTIFIER_STABILITY_INDEFINITE_DURATION,
		self::OPTION_XML_EXPORT_SUPPORTED => FALSE,
		self::OPTION_XML_IMPORT_SUPPORTED => FALSE,
		self::OPTION_UNFILED_CONTENT_SUPPORTED => FALSE,
		self::OPTION_VERSIONING_SUPPORTED => FALSE,
		self::OPTION_SIMPLE_VERSIONING_SUPPORTED => FALSE,
		self::OPTION_ACCESS_CONTROL_SUPPORTED => FALSE,
		self::OPTION_LOCKING_SUPPORTED => FALSE,
		self::OPTION_OBSERVATION_SUPPORTED => FALSE,
		self::OPTION_JOURNALED_OBSERVATION_SUPPORTED => FALSE,
		self::OPTION_RETENTION_SUPPORTED => FALSE,
		self::OPTION_LIFECYCLE_SUPPORTED => FALSE,
		self::OPTION_TRANSACTIONS_SUPPORTED => FALSE,
		self::OPTION_WORKSPACE_MANAGEMENT_SUPPORTED => FALSE,
		self::OPTION_UPDATE_PRIMARY_NODETYPE_SUPPORTED => FALSE,
		self::OPTION_UPDATE_MIXIN_NODETYPES_SUPPORTED => FALSE,
		self::OPTION_SHAREABLE_NODES_SUPPORTED => FALSE,
		self::OPTION_NODE_TYPE_MANAGEMENT_SUPPORTED => TRUE,
		self::NODE_TYPE_MANAGEMENT_INHERITANCE => self::NODE_TYPE_MANAGEMENT_INHERITANCE_MINIMAL,
		self::NODE_TYPE_MANAGEMENT_OVERRIDES_SUPPORTED => FALSE,
		self::NODE_TYPE_MANAGEMENT_PRIMARY_ITEM_NAME_SUPPORTED => FALSE,
		self::NODE_TYPE_MANAGEMENT_ORDERABLE_CHILD_NODES_SUPPORTED => FALSE,
		self::NODE_TYPE_MANAGEMENT_RESIDUAL_DEFINITIONS_SUPPORTED => FALSE,
		self::NODE_TYPE_MANAGEMENT_AUTOCREATED_DEFINITIONS_SUPPORTED => FALSE,
		self::NODE_TYPE_MANAGEMENT_SAME_NAME_SIBLINGS_SUPPORTED => TRUE,
		self::NODE_TYPE_MANAGEMENT_PROPERTY_TYPES => array(),
		self::NODE_TYPE_MANAGEMENT_MULTIVALUED_PROPERTIES_SUPPORTED => TRUE,
		self::NODE_TYPE_MANAGEMENT_MULTIPLE_BINARY_PROPERTIES_SUPPORTED => TRUE,
		self::NODE_TYPE_MANAGEMENT_VALUE_CONSTRAINTS_SUPPORTED => FALSE,
		self::QUERY_LANGUAGES => array(
			\F3\PHPCR\Query\QueryInterface::JCR_JQOM
		),
		self::QUERY_STORED_QUERIES_SUPPORTED => FALSE,
		self::QUERY_FULL_TEXT_SEARCH_SUPPORTED => TRUE,
		self::QUERY_JOINS => self::QUERY_JOINS_NONE
	);

	/**
	 * Constructs a Repository object.
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Injects the configuration manager
	 *
	 * @param \F3\FLOW3\Configuration\Manager $configurationManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectConfigurationManager(\F3\FLOW3\Configuration\Manager $configurationManager) {
		$this->settings = $configurationManager->getSettings('TYPO3CR');
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
	 * @param \F3\PHPCR\Credentials $credentials The credentials of the user
	 * @param string $workspaceName the name of a workspace
	 * @return \F3\TYPO3CR\Session a valid session for the user to access the repository
	 * @throws \F3\PHPCR\LoginException If the login fails
	 * @throws \F3\PHPCR\NoSuchWorkspacexception If the specified workspaceName is not recognized
	 * @throws \F3\PHPCR\RepositoryException if another error occurs
	 * @todo Currently given credentials are not checked at all!
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function login($credentials = NULL, $workspaceName = 'default') {
		if ($credentials !== NULL && !($credentials instanceof \F3\PHPCR\CredentialsInterface)) throw new \F3\PHPCR\RepositoryException('$credentials must be an instance of \F3\PHPCR\CredentialsInterface', 1181042933);
		if ($workspaceName !== 'default') {
			throw new \F3\PHPCR\NoSuchWorkspaceException('Only default workspace supported', 1181063009);
		}

		$this->storageBackend = $this->objectFactory->create($this->settings['storage']['backend'], $this->settings['storage']['backendOptions']);
		$this->storageBackend->setSearchEngine($this->objectFactory->create($this->settings['search']['backend'], $this->settings['search']['backendOptions']));
		$this->storageBackend->setWorkspaceName($workspaceName);
		$this->storageBackend->connect();

		$session = $this->objectFactory->create('F3\PHPCR\SessionInterface', $workspaceName, $this, $this->storageBackend);
		$this->storageBackend->setNamespaceRegistry($session->getWorkspace()->getNamespaceRegistry());
		return $session;
	}

	/**
	 * Returns a string array holding all descriptor keys available for this
	 * implementation, both the standard descriptors defined by the string
	 * constants in this interface and any implementation-specific descriptors.
	 * Used in conjunction with getDescriptorValue($key) and getDescriptorValues($key)
	 * to query information about this repository implementation.
	 *
	 * @return array a string array holding all descriptor keys
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDescriptorKeys() {
		return array_keys($this->standardDescriptors);
	}

	/**
	 * Returns TRUE if $key is a standard descriptor
	 * defined by the string constants in this interface and FALSE if it is
	 * either a valid implementation-specific key or not a valid key.
	 *
	 * @param string $key a descriptor key.
	 * @return boolan whether $key is a standard descriptor.
	 */
	public function isStandardDescriptor($key) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1224598717);
	}

	/**
	 * Returns TRUE if $key is a valid single-value descriptor;
	 * otherwise returns FALSE.
	 *
	 * @param string $key a descriptor key.
	 * @return boolean whether the specified descriptor is multi-valued.
	 */
	public function isSingleValueDescriptor($key) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1224598718);
	}

	/**
	 * The value of a single-value descriptor is found by
	 * passing the key for that descriptor to this method.
	 * If $key is the key of a multi-value descriptor
	 * or not a valid key this method returns NULL.
	 *
	 * @param string $key a descriptor key.
	 * @return \F3\PHPCR\ValueInterface The value of the indicated descriptor
	 */
	public function getDescriptorValue($key) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1224598719);
	}

	/**
	 * The value array of a multi-value descriptor is found by
	 * passing the key for that descriptor to this method.
	 * If $key is the key of a single-value descriptor
	 * then this method returns that value as an array of size one.
	 * If $key is not a valid key this method returns NULL.
	 *
	 * @param string $key a descriptor key.
	 * @return array of \F3\PHPCR\ValueInterface the value array for the indicated descriptor
	 */
	public function getDescriptorValues($key) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1224598720);
	}

	/**
	 * A convenience method. The call
	 *  String s = repository.getDescriptor(key);
	 * is equivalent to
	 *  Value v = repository.getDescriptor(key);
	 *  String s = (v == null) ? null : v.getString();
	 *
	 * @param key a descriptor key.
	 * @return a descriptor value in string form.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDescriptor($key) {
		return (isset($this->standardDescriptors[$key]) ? $this->standardDescriptors[$key] : NULL);
	}

}

?>