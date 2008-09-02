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
 * @subpackage Storage
 * @version $Id:F3_TYPO3CR_Storage_Backend_PDO.php 888 2008-05-30 16:00:05Z k-fish $
 */

require_once('Zend/Search/Lucene.php');

/**
 * A storage indexing/search backend using Zend_Lucene
 *
 * @package TYPO3CR
 * @subpackage Storage
 * @version $Id:F3_TYPO3CR_Storage_Backend_PDO.php 888 2008-05-30 16:00:05Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Storage_Search_Lucene extends F3_TYPO3CR_Storage_AbstractSearch {

	/**
	 * @var string
	 */
	protected $indexLocation;

	/**
	 * @var Zend_Search_Lucene_Interface
	 */
	protected $index;

	/**
	 * Constructs the Lucene backend
	 *
	 * @param array $options
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($options = array()) {
		parent::__construct($options);
		Zend_Search_Lucene_Analysis_Analyzer::setDefault(new F3_TYPO3CR_Storage_Search_LuceneKeywordAnalyser());
	}

	/**
	 * Setter for the Lucene index location (a path).
	 *
	 * @param string $indexLocation
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setIndexLocation($indexLocation) {
		$this->indexLocation = $indexLocation;
		$this->connect();
	}

	/**
	 * Opens the Lucene index
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function connect() {
		try {
			$this->index = Zend_Search_Lucene::open(F3_FLOW3_Utility_Files::concatenatePaths(array($this->indexLocation, $this->workspaceName)));
		} catch (Zend_Search_Lucene_Exception $e) {
			throw new F3_TYPO3CR_StorageException('Could not open Lucene index - did you configure the (correct) location? ' . $e->getMessage(), 1219320933);
		}
	}

	/**
	 * Adds the given node to the index
	 *
	 * @param F3_PHPCR_NodeInterface $node
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addNode(F3_PHPCR_NodeInterface $node) {
		$nodeDocument = new Zend_Search_Lucene_Document();
		$nodeDocument->addField(Zend_Search_Lucene_Field::Keyword('identifier', $node->getIdentifier()));
		$nodeDocument->addField(Zend_Search_Lucene_Field::Keyword('nodetype', $node->getPrimaryNodeType()->getName()));
		$nodeDocument->addField(Zend_Search_Lucene_Field::Keyword('path', $node->getPath()));

		foreach ($node->getProperties() as $property) {
			try {
				$nodeDocument->addField(Zend_Search_Lucene_Field::UnStored($property->getName(), $property->getString()));
			} catch (F3_PHPCR_ValueFormatException $e) {
				foreach ($property->getValues() as $value) {
					$nodeDocument->addField(Zend_Search_Lucene_Field::UnStored($property->getName(), $value->getString()));
				}
			}
		}

		$this->index->addDocument($nodeDocument);
	}

	/**
	 * Updates the given node in the index
	 *
	 * @param F3_PHPCR_NodeInterface $node
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateNode(F3_PHPCR_NodeInterface $node) {
		$this->deleteNode($node);
		$this->addNode($node);
	}

	/**
	 * Deletes the given node from the index
	 *
	 * @param F3_PHPCR_NodeInterface $node
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function deleteNode(F3_PHPCR_NodeInterface $node) {
		$hits = $this->index->find(new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term($node->getIdentifier(), 'identifier'), TRUE));
		foreach ($hits as $hit) {
			$this->index->delete($hit->id);
		}
	}

	/**
	 * Returns an array with identifiers matching the query
	 *
	 * @param F3_PHPCR_Query_QOM_QueryObjectModelInterface $query
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findNodeIdentifiers(F3_PHPCR_Query_QOM_QueryObjectModelInterface $query) {
		$luceneQuery = new Zend_Search_Lucene_Search_Query_MultiTerm();

		if ($query->getSource() instanceof F3_PHPCR_Query_QOM_SourceInterface) {
			$term  = new Zend_Search_Lucene_Index_Term($query->getSource()->getNodeTypeName(), 'nodetype');
			$luceneQuery->addTerm($term, TRUE);
		}

		$constraint = $query->getConstraint();
		if ($constraint !== NULL) {
			$this->parseConstraint($constraint, $query->getBoundVariableValues(), $luceneQuery);
		}

		$hits = $this->index->find($luceneQuery);
		$result = array();
		foreach ($hits as $hit) {
			$result[] = $hit->identifier;
		}

		return $result;
	}

	/**
	 * Transforms a constraint into Lucene search terms added to the query
	 *
	 * @param F3_PHPCR_Query_QOM_ConstraintInterface $constraint
	 * @param Zend_Search_Lucene_Search_Query_MultiTerm $luceneQuery
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseConstraint(F3_PHPCR_Query_QOM_ConstraintInterface $constraint, array $boundVariableValues, Zend_Search_Lucene_Search_Query_MultiTerm $luceneQuery) {
		if ($constraint instanceof F3_PHPCR_Query_QOM_ComparisonInterface) {
			$term  = new Zend_Search_Lucene_Index_Term($boundVariableValues[$constraint->getOperand1()->getPropertyName()], $constraint->getOperand1()->getPropertyName());
			$luceneQuery->addTerm($term, TRUE);
		}
	}

}

?>
