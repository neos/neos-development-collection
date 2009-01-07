<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Storage\Search;

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
 * @subpackage Storage
 * @version $Id:\F3\TYPO3CR\Storage\Backend::PDO.php 888 2008-05-30 16:00:05Z k-fish $
 */

require_once('Zend/Search/Lucene.php');

/**
 * A storage indexing/search backend using Zend_Lucene
 *
 * @package TYPO3CR
 * @subpackage Storage
 * @version $Id:\F3\TYPO3CR\Storage\Backend::PDO.php 888 2008-05-30 16:00:05Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @scope prototype
 */
class Lucene extends \F3\TYPO3CR\Storage\AbstractSearch {

	/**
	 * @var string
	 */
	protected $indexLocation;

	/**
	 * @var \Zend_Search_Lucene_Interface
	 */
	protected $index;

	/**
	 * Constructs the Lucene backend
	 *
	 * @param array $options
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($options = array(), \F3\FLOW3\Object\FactoryInterface $objectFactory) {
		parent::__construct($options);
		\Zend_Search_Lucene_Analysis_Analyzer::setDefault($objectFactory->create('F3\Lucene\KeywordAnalyser'));
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
			$this->index = \Zend_Search_Lucene::open(\F3\FLOW3\Utility\Files::concatenatePaths(array($this->indexLocation, $this->workspaceName)));
		} catch (\Zend_Search_Lucene_Exception $e) {
			throw new \F3\TYPO3CR\StorageException('Could not open Lucene index - did you configure the (correct) location? ' . $e->getMessage(), 1219320933);
		}
	}

	/**
	 * Adds the given node to the index
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo get rid of try/catch to detect multivalued properties
	 */
	public function addNode(\F3\PHPCR\NodeInterface $node) {
		$nodeDocument = new \Zend_Search_Lucene_Document();
		$nodeDocument->addField(\Zend_Search_Lucene_Field::keyword('typo3cr:identifier', $node->getIdentifier(), 'utf-8'));
		$nodeDocument->addField(\Zend_Search_Lucene_Field::keyword('typo3cr:nodetype', $node->getPrimaryNodeType()->getName(), 'utf-8'));
		$nodeDocument->addField(\Zend_Search_Lucene_Field::keyword('typo3cr:path', $node->getPath(), 'utf-8'));

		foreach ($node->getProperties() as $property) {
			try {
					// create a field that is unstored and not tokenised, no factory method available
				$nodeDocument->addField(new \Zend_Search_Lucene_Field($property->getName(), $property->getString(), 'utf-8', FALSE, TRUE, FALSE));
			} catch (\F3\PHPCR\ValueFormatException $e) {
				foreach ($property->getValues() as $value) {
					$nodeDocument->addField(new \Zend_Search_Lucene_Field($property->getName(), $value->getString(), 'utf-8', FALSE, TRUE, FALSE));
				}
			}
		}

		$this->index->addDocument($nodeDocument);
	}

	/**
	 * Updates the given node in the index
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateNode(\F3\PHPCR\NodeInterface $node) {
		$this->deleteNode($node);
		$this->addNode($node);
	}

	/**
	 * Deletes the given node from the index
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function deleteNode(\F3\PHPCR\NodeInterface $node) {
		$hits = $this->index->find(new \Zend_Search_Lucene_Search_Query_Term(new \Zend_Search_Lucene_Index_Term($node->getIdentifier(), 'typo3cr:identifier'), TRUE));
		foreach ($hits as $hit) {
			$this->index->delete($hit->id);
		}
	}

	/**
	 * Returns an array with identifiers matching the query
	 *
	 * @param \F3\PHPCR\Query\QOM\QueryObjectModelInterface $query
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findNodeIdentifiers(\F3\PHPCR\Query\QOM\QueryObjectModelInterface $query) {
		$luceneQuery = new \Zend_Search_Lucene_Search_Query_MultiTerm();

		if ($query->getSource() instanceof \F3\PHPCR\Query\QOM\SourceInterface) {
			$term  = new \Zend_Search_Lucene_Index_Term($query->getSource()->getNodeTypeName(), 'typo3cr:nodetype');
			$luceneQuery->addTerm($term, TRUE);
		}

		$constraint = $query->getConstraint();
		if ($constraint !== NULL) {
			$this->parseConstraint($constraint, $query->getBoundVariableValues(), $luceneQuery);
		}

		$hits = $this->index->find($luceneQuery);
		$result = array();
		foreach ($hits as $hit) {
			$result[] = $hit->getDocument()->getFieldValue('typo3cr:identifier');
		}

		return $result;
	}

	/**
	 * Transforms a constraint into Lucene search terms added to the query
	 *
	 * @param \F3\PHPCR\Query\QOM\ConstraintInterface $constraint
	 * @param \Zend_Search_Lucene_Search_Query_MultiTerm $luceneQuery
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseConstraint(\F3\PHPCR\Query\QOM\ConstraintInterface $constraint, array $boundVariableValues, \Zend_Search_Lucene_Search_Query_MultiTerm $luceneQuery) {
		if ($constraint instanceof \F3\PHPCR\Query\QOM\ComparisonInterface) {
			$term  = new \Zend_Search_Lucene_Index_Term($boundVariableValues[$constraint->getOperand1()->getPropertyName()], 'flow3:' . $constraint->getOperand1()->getPropertyName());
			$luceneQuery->addTerm($term, TRUE);
		}
	}

}

?>
