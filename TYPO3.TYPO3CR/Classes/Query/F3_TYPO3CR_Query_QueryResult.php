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
 * A QueryResult object. Returned by Query->execute().
 *
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class QueryResult implements F3::PHPCR::Query::QueryResultInterface {

	/**
	 * Injects the Component Factory
	 */
	protected $componentFactory;

	/**
	 * @var F3::TYPO3CR::SessionInterface
	 */
	protected $session;

	/**
	 * @var array
	 */
	protected $identifiers;

	/**
	 * Constructs this QueryResult
	 *
	 * @param array $identifiers
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(array $identifiers) {
		$this->identifiers = $identifiers;
	}

	/**
	 * Injects the Component Factory
	 *
	 * @param F3::FLOW3::Component::FactoryInterface $componentFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectComponentFactory(F3::FLOW3::Component::FactoryInterface $componentFactory) {
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Injects the session for this query
	 *
	 * @param F3::PHPCR::SessionInterface $session
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectSession(F3::PHPCR::SessionInterface $session) {
		$this->session = $session;
	}

	/**
	 * Returns an array of all the column names in the table view of this result set.
	 *
	 * @return array
	 * @throws F3::PHPCR::RepositoryException if an error occurs.
	 */
	public function getColumnNames() {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1216897579);
	}

	/**
	 * Returns an iterator over the Rows of the result table. The rows are
	 * returned according to the ordering specified in the query.
	 *
	 * @return F3::PHPCR::Query::RowIteratorInterface a RowIterator
	 * @throws F3::PHPCR::RepositoryException if an error occurs.
	*/
	public function getRows() {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1216897580);
	}

	/**
	 * Returns an iterator over all nodes that match the query. The rows are
	 * returned according to the ordering specified in the query.
	 *
	 * @return F3::PHPCR::NodeIteratorInterface a NodeIterator
	 * @throws F3::PHPCR::RepositoryException if the query contains more than one selector or if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getNodes() {
		$nodeIterator = $this->componentFactory->getComponent('F3::PHPCR::NodeIteratorInterface');
		foreach ($this->identifiers as $identifier) {
			$nodeIterator->append($this->session->getNodeByIdentifier($identifier));
		}

		return $nodeIterator;
	}

}
?>