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
class F3_TYPO3CR_Query_QueryResult implements F3_PHPCR_Query_QueryResultInterface {

	/**
	 * Injects the Component Factory
	 */
	protected $componentFactory;

	/**
	 * @var F3_TYPO3CR_SessionInterface
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
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectComponentFactory(F3_FLOW3_Component_FactoryInterface $componentFactory) {
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Injects the session for this query
	 *
	 * @param F3_PHPCR_SessionInterface $session
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectSession(F3_PHPCR_SessionInterface $session) {
		$this->session = $session;
	}

	/**
	 * Returns an array of all the column names in the table view of this result set.
	 *
	 * @return array
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
	 */
	public function getColumnNames() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1216897579);
	}

	/**
	 * Returns an iterator over the Rows of the result table. The rows are
	 * returned according to the ordering specified in the query.
	 *
	 * @return F3_PHPCR_Query_RowIteratorInterface a RowIterator
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
	*/
	public function getRows() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1216897580);
	}

	/**
	 * Returns an iterator over all nodes that match the query. The rows are
	 * returned according to the ordering specified in the query.
	 *
	 * @return F3_PHPCR_NodeIteratorInterface a NodeIterator
	 * @throws F3_PHPCR_RepositoryException if the query contains more than one selector or if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getNodes() {
		$nodeIterator = $this->componentFactory->getComponent('F3_PHPCR_NodeIteratorInterface');
		foreach ($this->identifiers as $identifier) {
			$nodeIterator->append($this->session->getNodeByIdentifier($identifier));
		}

		return $nodeIterator;
	}

}
?>