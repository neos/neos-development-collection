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

/**
 * A Storage backend using PDO
 *
 * @package TYPO3CR
 * @subpackage Storage
 * @version $Id:F3_TYPO3CR_Storage_Backend_PDO.php 888 2008-05-30 16:00:05Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Storage_Backend_PDO extends F3_TYPO3CR_Storage_AbstractSQLBackend {

	/**
	 * @var PDO
	 */
	protected $databaseHandle;

	/**
	 * @var string Name of the current workspace
	 */
	protected $workspaceName = 'default';

	/**
	 * Connect to the database
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function connect() {
		try {
			$this->databaseHandle = new PDO($this->dataSourceName, $this->username, $this->password);
			$this->databaseHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			throw new F3_TYPO3CR_StorageException('Could not connect to DSN "' . $this->dataSourceName . '". PDO error: ' . $e->getMessage());
		}
	}

	/**
	 * Sets the name of the current workspace
	 *
	 * @param  string $workspaceName Name of the workspace which should be used for all storage operations
	 * @return void
	 * @throws InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setWorkspaceName($workspaceName) {
		if ($workspaceName == '' || !is_string($workspaceName)) throw new InvalidArgumentException('"' . $workspaceName . '" is not a valid workspace name.', 1200614989);
		$this->workspaceName = $workspaceName;
	}

	/**
	 * Fetches raw node data from the database
	 *
	 * @param string $identifier The Identifier of the node to fetch
	 * @return array|FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRawNodeByIdentifier($identifier) {
		$statementHandle = $this->databaseHandle->prepare('SELECT parent, name, identifier, nodetype FROM nodes WHERE identifier = ?');
		$statementHandle->execute(array($identifier));
		return $statementHandle->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Fetches raw node data of the root node of the current workspace.
	 *
	 * @return array|FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRawRootNode() {
		try {
			$statementHandle = $this->databaseHandle->prepare('SELECT parent, name, identifier, nodetype FROM nodes WHERE parent =\'\'');
			$statementHandle->execute();
			return $statementHandle->fetch(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			throw new F3_TYPO3CR_StorageException('Could not read raw root node. Make sure the database is initialized correctly (php index_dev.php TYPO3CR Setup database). PDO error: ' . $e->getMessage(), 1216051737);
		}
	}

	/**
	 * Fetches sub node Identifiers from the database
	 *
	 * @param string $identifier The node Identifier to fetch (sub-)nodes for
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifiersOfSubNodesOfNode($identifier) {
		$nodeIdentifiers = array();
		$statementHandle = $this->databaseHandle->prepare('SELECT identifier FROM nodes WHERE parent = ?');
		$statementHandle->execute(array($identifier));
		$rawNodes = $statementHandle->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rawNodes as $rawNode) {
			$nodeIdentifiers[] = $rawNode['identifier'];
		}
		return $nodeIdentifiers;
	}

	/**
	 * Fetches raw property data from the database
	 *
	 * @param string $identifier The node Identifier to fetch properties for
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRawPropertiesOfNode($identifier) {
		$statementHandle = $this->databaseHandle->prepare('SELECT name, value, namespace, multivalue, type FROM properties WHERE parent = ?');
		$statementHandle->execute(array($identifier));
		$properties = $statementHandle->fetchAll(PDO::FETCH_ASSOC);
		if (is_array($properties)) {
			foreach ($properties as &$property) {
				$property['value'] = unserialize($property['value']);
			}
		}
		return $properties;
	}

	/**
	 * Fetches raw data for all nodetypes from the database
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRawNodeTypes() {
		try {
			$statementHandle = $this->databaseHandle->query('SELECT name FROM nodetypes');
			$nodetypes = $statementHandle->fetchAll(PDO::FETCH_ASSOC);
			return $nodetypes;
		} catch (PDOException $e) {
			throw new F3_TYPO3CR_StorageException('Could not read raw nodetypes. Make sure the database is initialized correctly (php index_dev.php TYPO3CR Setup database). PDO error: ' . $e->getMessage(), 1216051821);
		}
	}

	/**
	 * Fetches raw nodetype data from the database
	 *
	 * @param string $nodeTypeNme The name of the nodetype record to fetch
	 * @return array|FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRawNodeType($nodeTypeName) {
		$statementHandle = $this->databaseHandle->prepare('SELECT name FROM nodetypes WHERE name = ?');
		$statementHandle->execute(array($nodeTypeName));
		return $statementHandle->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Adds the given nodetype to the database
	 *
	 * @param F3_PHPCR_NodeType_NodeTypeDefinitionInterface $nodeTypeDefinition
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addNodeType(F3_PHPCR_NodeType_NodeTypeDefinitionInterface $nodeTypeDefinition) {
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO nodetypes (name) VALUES (?)');
		$statementHandle->execute(array(
			$nodeTypeDefinition->getName()
		));
	}

		/**
	 * Deletes the named nodetype from the database
	 *
	 * @param string $name
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function deleteNodeType($name) {
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM nodetypes WHERE name=?');
		$statementHandle->execute(array($name));
	}

	/**
	 * Adds a node to the storage
	 *
	 * @param F3_PHPCR_NodeInterface $node node to insert
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addNode(F3_PHPCR_NodeInterface $node) {
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO nodes (identifier, parent, nodetype, name) VALUES (?, ?, ?, ?)');
		$statementHandle->execute(array($node->getIdentifier(), $node->getParent()->getIdentifier(), $node->getPrimaryNodeType()->getName(), $node->getName()));
	}

	/**
	 * Updates a node in the storage
	 *
	 * @param F3_PHPCR_NodeInterface $node node to update
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateNode(F3_PHPCR_NodeInterface $node) {
		if ($node->getDepth() > 0) {
			$statementHandle = $this->databaseHandle->prepare('UPDATE nodes SET parent=?, nodetype=?, name=? WHERE identifier=?');
			$statementHandle->execute(array($node->getParent()->getIdentifier(), $node->getPrimaryNodeType()->getName(), $node->getName(), $node->getIdentifier()));
		}
	}

	/**
	 * Deletes a node in the repository
	 *
	 * @param F3_PHPCR_NodeInterface $node node to delete
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeNode(F3_PHPCR_NodeInterface $node) {
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM nodes WHERE identifier=?');
		$statementHandle->execute(array($node->getIdentifier()));
	}

	/**
	 * Adds a property in the storage
	 *
	 * @param F3_PHPCR_PropertyInterface $property property to insert
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addProperty(F3_PHPCR_PropertyInterface $property) {
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO properties (parent, name, value, namespace, multivalue, type) VALUES (?, ?, ?, \'\', ?, ?)');
		$statementHandle->execute(array(
			$property->getParent()->getIdentifier(),
			$property->getName(),
			$property->getSerializedValue(),
			(integer)$property->isMultiple(),
			$property->getType()
		));
	}

	/**
	 * Updates a property in the repository identified by identifier and name
	 *
	 * @param F3_PHPCR_PropertyInterface $property property to update
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateProperty(F3_PHPCR_PropertyInterface $property) {
		$statementHandle = $this->databaseHandle->prepare('UPDATE properties SET value=?, type=? WHERE parent=? AND name=?');
		$statementHandle->execute(array($property->getSerializedValue(), $property->getType(), $property->getParent()->getIdentifier(), $property->getName()));
	}

	/**
	 * Deletes a property in the repository identified by identifier and name
	 *
	 * @param F3_PHPCR_PropertyInterface $property property to remove
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeProperty(F3_PHPCR_PropertyInterface $property) {
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM properties WHERE parent=? AND name=?');
		$statementHandle->execute(array($property->getParent()->getIdentifier(), $property->getName()));
	}

	/**
	 * Fetches raw namespace data from the database
	 *
	 * @return array
	 * @author Sebastian Kurfürst <sebastian@ŧypo3.org>
	 */
	public function getRawNamespaces() {
		$statementHandle = $this->databaseHandle->query('SELECT prefix, uri FROM namespaces');
		$namespaces = $statementHandle->fetchAll(PDO::FETCH_ASSOC);
		return $namespaces;
	}

	/**
	 * Updates the prefix for the namespace identified by $uri
	 *
	 * @param string $prefix The prefix of the namespace to update
	 * @param string $uri The URI of the namespace to update
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateNamespacePrefix($prefix, $uri) {
		$statementHandle = $this->databaseHandle->prepare('UPDATE namespaces SET prefix=? WHERE uri=?');
		$statementHandle->execute(array($prefix,$uri));
	}

	/**
	 * Updates the URI for the namespace identified by $prefix
	 *
	 * @param string $prefix The prefix of the namespace to update
	 * @param string $uri The URI of the namespace to update
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateNamespaceURI($prefix, $uri) {
		$statementHandle = $this->databaseHandle->prepare('UPDATE namespaces SET uri=? WHERE prefix=?');
		$statementHandle->execute(array($uri,$prefix));
	}

	/**
	 * Adds a namespace identified by prefix and URI
	 *
	 * @param string $prefix The namespace prefix to register
	 * @param string $uri The namespace URI to register
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function addNamespace($prefix, $uri) {
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO namespaces (prefix,uri) VALUES (?, ?)');
		$statementHandle->execute(array($prefix,$uri));
	}

	/**
	 * Deletes the namespace identified by $prefix.
	 *
	 * @param string $prefix The prefix of the namespace to delete
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function deleteNamespace($prefix) {
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM namespaces WHERE prefix=?');
		$statementHandle->execute(array($prefix));
	}

	/**
	 * Returns an array with identifiers matching the query
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findNodeIdentifiers(F3_PHPCR_Query_QOM_QueryObjectModelInterface $query) {
		$variables = array();

		if ($query->getConstraint() !== NULL) {
			$statement = 'SELECT "nodes"."identifier" FROM "nodes" LEFT JOIN "properties" ON ';
			$statement .= '"nodes"."identifier"="properties"."parent" WHERE ';
			list($parsedConstraint, $variables) = $this->parseConstraint($query->getConstraint());
			$statement .= $parsedConstraint;
			$statement .= ' AND ';
		} else {
			$statement = 'SELECT "identifier" FROM "nodes" WHERE ';
		}

		if ($query->getSource() instanceof F3_PHPCR_Query_QOM_SourceInterface) {
			$identifier = ':' . md5('TYPO3CR:nodes:nodetype');
			$statement .= '"nodetype" = ' . $identifier;
			$variables[$identifier] = $query->getSource()->getNodeTypeName();
		}

		$boundVariableValues = $query->getBoundVariableValues();
		array_walk($boundVariableValues, static function (&$value, $key) { $value = serialize($value); });
		$variables = array_merge($variables, $boundVariableValues);

		$statementHandle = $this->databaseHandle->prepare($statement);
		$result = $statementHandle->execute($variables);
		if ($result === FALSE) {
			throw new F3_TYPO3CR_StorageException($statementHandle->errorInfo(), 1218021423);
		}
		$result = $statementHandle->fetchAll(PDO::FETCH_COLUMN, 0);

		return (array)$result;
	}

	/**
	 * Transforms a constraint into the corresponding SQL clause
	 *
	 * @param F3_PHPCR_Query_QOM_ConstraintInterface $constraint
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function parseConstraint(F3_PHPCR_Query_QOM_ConstraintInterface $constraint) {
		$statement = '';
		$variables = array();

		if ($constraint instanceof F3_PHPCR_Query_QOM_ComparisonInterface) {
			$nameIdentifier = ':' . md5('TYPO3CR:properties:name:' . $constraint->getOperand1()->getPropertyName());
			$valueIdentifier = ':' . md5('TYPO3CR:properties:value:' . $constraint->getOperand1()->getPropertyName());
			$variables[$nameIdentifier] = $constraint->getOperand1()->getPropertyName();
			$statement .= '("properties"."name" = ' . $nameIdentifier . ' AND "properties"."value" ';
			$statement .= $this->operatorTypeToSQL($constraint->getOperator()) . $valueIdentifier . ')';
		}

		return array($statement, $variables);
	}

	/**
	 * Maps F3_PHPCR_Query_QOM_QueryObjectModelConstantsInterface operator constants to SQL operators
	 *
	 * @param integer $operatorType
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function operatorTypeToSQL($operatorType) {
		switch ($operatorType) {
			case F3_PHPCR_Query_QOM_QueryObjectModelConstantsInterface::OPERATOR_EQUAL_TO:
				$operator = '=';
				break;
			default:
				throw new F3_TYPO3CR_StorageException('Unsupported operator in query building encountered.', 1218020096);
		}

		return $operator;
	}
}

?>