<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Query;

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
 * @subpackage Query
 * @version $Id$
 */

/**
 * A QueryResult object. Returned by Query->execute().
 *
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class QueryResult implements \F3\PHPCR\Query\QueryResultInterface {

	/**
	 * Injects the Object Factory
	 */
	protected $objectFactory;

	/**
	 * @var \F3\PHPCR\SessionInterface
	 */
	protected $session;

	/**
	 * @var array
	 */
	protected $identifierTuples;

	/**
	 * Constructs this QueryResult
	 *
	 * $identifierTuples is expected to be like this:
	 * array(
	 *  array('selectorA' => '12345', 'selectorB' => '67890')
	 *  array('selectorA' => '54321', 'selectorB' => '09876')
	 * )
	 *
	 * @param array $identifiers
	 * @param \F3\PHPCR\SessionInterface $session
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(array $identifierTuples, \F3\PHPCR\SessionInterface $session) {
		$this->identifierTuples = $identifierTuples;
		$this->session = $session;
	}

	/**
	 * Injects the Object Factory
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Returns an array of all the column names in the table view of this result set.
	 *
	 * @return array array holding the column names.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 */
	public function getColumnNames() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1216897579);
	}

	/**
	 * Returns an iterator over the Rows of the result table. The rows are
	 * returned according to the ordering specified in the query.
	 *
	 * @return \F3\PHPCR\Query\RowIteratorInterface a RowIterator
	 * @throws \F3\PHPCR\RepositoryException if this call is the second time either getRows() or getNodes() has been called on the same QueryResult object or if another error occurs.
	*/
	public function getRows() {
		if ($this->identifierTuples === NULL) throw new \F3\PHPCR\RepositoryException('Illegal getRows() call - can be called only once and not after getNodes().', 1237991809);

		$rowIterator = $this->objectFactory->create('F3\PHPCR\Query\RowIteratorInterface');
		foreach ($this->identifierTuples as $identifierTuple) {
			$rowIterator->append(
				$this->objectFactory->create('F3\PHPCR\Query\RowInterface', $identifierTuple)
			);
		}
		$this->identifierTuples = NULL;

		return $rowIterator;
	}

	/**
	 * Returns an iterator over all nodes that match the query. The nodes are
	 * returned according to the ordering specified in the query.
	 *
	 * @return \F3\PHPCR\NodeIteratorInterface a NodeIterator
	 * @throws \F3\PHPCR\RepositoryException if the query contains more than one selector, if this call is the second time either getRows() or getNodes() has been called on the same QueryResult object or if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getNodes() {
		if ($this->identifierTuples === NULL) throw new \F3\PHPCR\RepositoryException('Illegal getNodes() call - can be called only once and not after getRows().', 1237991684);
		if (count(current($this->identifierTuples)) > 1) throw new \F3\PHPCR\RepositoryException('getNodes() can be called only on results having a single selector.', 1237992322);

		$nodeIterator = $this->objectFactory->create('F3\PHPCR\NodeIteratorInterface');
		foreach ($this->identifierTuples as $identifierTuple) {
			$nodeIterator->append($this->session->getNodeByIdentifier(current($identifierTuple)));
		}
		$this->identifierTuples = NULL;

		return $nodeIterator;
	}

}
?>