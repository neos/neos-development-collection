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
 * A query in the JCR query object model.
 *
 * The JCR query object model describes the queries that can be evaluated by a JCR
 * repository independent of any particular query language, such as SQL.
 *
 * A query consists of:
 *
 * a source. When the query is evaluated, the source evaluates its selectors and
 * the joins between them to produce a (possibly empty) set of node-tuples. This
 * is a set of 1-tuples if the query has one selector (and therefore no joins), a
 * set of 2-tuples if the query has two selectors (and therefore one join), a set
 * of 3-tuples if the query has three selectors (two joins), and so forth.
 * an optional constraint. When the query is evaluated, the constraint filters the
 * set of node-tuples.
 * a list of zero or more orderings. The orderings specify the order in which the
 * node-tuples appear in the query results. The relative order of two node-tuples
 * is determined by evaluating the specified orderings, in list order, until
 * encountering an ordering for which one node-tuple precedes the other. If no
 * orderings are specified, or if for none of the specified orderings does one
 * node-tuple precede the other, then the relative order of the node-tuples is
 * implementation determined (and may be arbitrary).
 * a list of zero or more columns to include in the tabular view of the query
 * results. If no columns are specified, the columns available in the tabular view
 * are implementation determined, but minimally include, for each selector, a column
 * for each single-valued non-residual property of the selector's node type.
 *
 * The query object model representation of a query is created by factory methods in the QueryObjectModelFactory.
 *
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_TYPO3CR_Query_QOM_QueryObjectModel extends F3_TYPO3CR_Query_PreparedQuery implements F3_PHPCR_Query_QOM_QueryObjectModelInterface {

	/**
	 * @var F3_PHPCR_Query_QOM_SourceInterface
	 */
	protected $source;

	/**
	 * @var F3_PHPCR_Query_QOM_ConstraintInterface
	 */
	protected $constraint;

	/**
	 * @var array
	 */
	protected $orderings;

	/**
	 * @var array
	 */
	protected $columns;

	/**
	 * Constructs this QueryObjectModel instance
	 *
	 * @param F3_PHPCR_Query_QOM_SourceInterface $selectorOrSource
	 * @param F3_PHPCR_Query_QOM_ConstraintInterface $constraint (null if none)
	 * @param array $orderings
	 * @param array $columns
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3_PHPCR_Query_QOM_SourceInterface $selectorOrSource, $constraint, array $orderings, array $columns) {
		$this->language = F3_PHPCR_Query_QueryInterface::JCR_JQOM;
		$this->source = $selectorOrSource;
		$this->constraint = $constraint;
		$this->orderings = $orderings;
		$this->columns = $columns;

		if ($this->constraint !== NULL) {
			$this->constraint->collectBoundVariableNames($this->boundVariableNames);
			$this->boundVariableNames = array_flip($this->boundVariableNames);
		}
	}

	/**
	 * Gets the node-tuple source for this query.
	 *
	 * @return F3_PHPCR_Query_QOM_SourceInterface the node-tuple source; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	*/
	public function getSource() {
		return $this->source;
	}

	/**
	 * Gets the constraint for this query.
	 *
	 * @return F3_PHPCR_Query_QOM_ConstraintInterface the constraint, or null if none
	 * @author Karsten Dambekalns <karsten@typo3.org>
	*/
	public function getConstraint() {
		return $this->constraint;
	}

	/**
	 * Gets the orderings for this query.
	 *
	 * @return array an array of zero or more F3_PHPCR_Query_QOM_OrderingInterface; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	*/
	public function getOrderings() {
		return $this->orderings;
	}

	/**
	 * Gets the columns for this query.
	 *
	 * @return array an array of zero or more F3_PHPCR_Query_QOM_ColumnInterface; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	*/
	public function getColumns() {
		return $this->columns;
	}

}
?>