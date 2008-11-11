<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::Query;

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
 * @subpackage Query
 * @version $Id$
 */

/**
 * This class encapsulates methods for the management of search queries.
 * Provides methods for the creation and retrieval of search queries.
 *
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class QueryManager implements F3::PHPCR::Query::QueryManagerInterface {

	/**
	 * @var F3::PHPCR::SessionInterface
	 */
	protected $session;

	/**
	 * @var F3::PHPCR::Query::QOM::QueryObjectModelFactoryInterface
	 */
	protected $queryObjectModelFactory;

	/**
	 * Constructs the query manager
	 *
	 * @param F3::PHPCR::SessionInterface $session
	 * @param F3::FLOW3::Object::ManagerInterface $objectManager
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3::PHPCR::SessionInterface $session, F3::FLOW3::Object::ManagerInterface $objectManager) {
		$this->session = $session;
		$this->queryObjectModelFactory = $objectManager->getObject('F3::PHPCR::Query::QOM::QueryObjectModelFactoryInterface', $this->session);
	}

	/**
	 * Creates a new query by specifying the query statement itself and the language
	 * in which the query is stated.
	 *
	 * @param string $statement
	 * @param string $language
	 * @return F3::PHPCR::Query::QueryInterface a Query object
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query statement is syntactically invalid or the specified language is not supported
	 * @throws F3::PHPCR::RepositoryException if another error occurs
	 */
	public function createQuery($statement, $language) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1216897622);
	}

	/**
	 * Creates a new prepared query by specifying the query statement itself and the language
	 * in which the query is stated.
	 *
	 * @param string $statement
	 * @param string $language
	 * @return F3::PHPCR::Query::PreparedQueryInterface a PreparedQuery object
	 * @throws F3::PHPCR::Query::InvalidQueryException if the query statement is syntactically invalid or the specified language is not supported
	 * @throws F3::PHPCR::RepositoryException if another error occurs
	 */
	public function createPreparedQuery($statement, $language) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1216897623);
	}

	/**
	 * Returns a QueryObjectModelFactory with which a JCR-JQOM query can be built
	 * programmatically.
	 *
	 * @return F3::PHPCR::Query::QOM::QueryObjectModelFactoryInterface a QueryObjectModelFactory object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getQOMFactory() {
		return $this->queryObjectModelFactory;
	}

	/*
	 * Retrieves an existing persistent query. If node is not a valid persisted
	 * query (that is, a node of type nt:query), an InvalidQueryException is thrown.
	 * Persistent queries are created by first using QueryManager.createQuery to
	 * create a Query object and then calling Query.save to persist the query to
	 * a location in the workspace.
	 *
	 * @param F3::PHPCR::NodeInterface $node a persisted query (that is, a node of type nt:query).
	 * @return F3::PHPCR::Query::QueryInterface a Query object.
	 * @throws F3::PHPCR::Query::InvalidQueryException If node is not a valid persisted query (that is, a node of type nt:query).
	 * @throws F3::PHPCR::RepositoryException if another error occurs
	 */
	public function getQuery($node) {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1216897625);
	}

	/**
	 * Returns an array of strings representing all query languages supported by
	 * this repository. In level 1 this set must include the strings represented
	 * by the constants Query.JCR_SQL2 and Query.JCR_JQOM. An implementation of
	 * either level may also support other languages.
	 *
	 * @return array A string array.
	 * @throws F3::PHPCR::RepositoryException if an error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getSupportedQueryLanguages() {
		return array(F3::PHPCR::Query::QueryInterface::JCR_JQOM);
	}

}

?>