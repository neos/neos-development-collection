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
			throw new F3_TYPO3CR_StorageException('Could not connect to DSN "' . $this->dataSourceName . '". PDO error: ' . $e->getMessage(), 1219326502);
		}
	}

	/**
	 * Disconnect from the storage backend
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function disconnect() {
		$this->databaseHandle = NULL;
	}

	/**
	 * Returns TRUE if the given identifier is used in storage.
	 *
	 * @param string $identifier
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasIdentifier($identifier) {
		$statementHandle = $this->databaseHandle->prepare('SELECT COUNT("identifier") FROM "nodes" WHERE "identifier" = ?');
		$statementHandle->execute(array($identifier));
		return ($statementHandle->fetchColumn() > 0);
	}



	/**
	 * Fetches raw node data of the root node of the current workspace.
	 *
	 * @return array|FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRawRootNode() {
		try {
			$statementHandle = $this->databaseHandle->prepare('SELECT "parent", "name", "identifier", "nodetype" FROM "nodes" WHERE "parent" =\'\'');
			$statementHandle->execute();
			return $statementHandle->fetch(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			throw new F3_TYPO3CR_StorageException('Could not read raw root node. Make sure the database is initialized correctly. PDO error: ' . $e->getMessage(), 1216051737);
		}
	}

	/**
	 * Fetches raw node data from the database
	 *
	 * @param string $identifier The Identifier of the node to fetch
	 * @return array|FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRawNodeByIdentifier($identifier) {
		$statementHandle = $this->databaseHandle->prepare('SELECT "parent", "name", "identifier", "nodetype" FROM "nodes" WHERE "identifier" = ?');
		$statementHandle->execute(array($identifier));
		return $statementHandle->fetch(PDO::FETCH_ASSOC);
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
		$statementHandle = $this->databaseHandle->prepare('SELECT "identifier" FROM "nodes" WHERE "parent" = ?');
		$statementHandle->execute(array($identifier));
		$rawNodes = $statementHandle->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rawNodes as $rawNode) {
			$nodeIdentifiers[] = $rawNode['identifier'];
		}
		return $nodeIdentifiers;
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
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO "nodes" ("identifier", "parent", "nodetype", "name") VALUES (?, ?, ?, ?)');
		$statementHandle->execute(array($node->getIdentifier(), $node->getParent()->getIdentifier(), $node->getPrimaryNodeType()->getName(), $node->getName()));
		$this->searchEngine->addNode($node);
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
			$statementHandle = $this->databaseHandle->prepare('UPDATE "nodes" SET "parent"=?, "nodetype"=?, "name"=? WHERE "identifier"=?');
			$statementHandle->execute(array($node->getParent()->getIdentifier(), $node->getPrimaryNodeType()->getName(), $node->getName(), $node->getIdentifier()));
			$this->searchEngine->updateNode($node);
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
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "nodes" WHERE "identifier"=?');
		$statementHandle->execute(array($node->getIdentifier()));
		$this->searchEngine->deleteNode($node);
	}

	/**
	 * Returns an array with identifiers matching the query
	 *
	 * @param F3_PHPCR_Query_QOM_QueryObjectModelInterface $query
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findNodeIdentifiers(F3_PHPCR_Query_QOM_QueryObjectModelInterface $query) {
		return $this->searchEngine->findNodeIdentifiers($query);
	}



	/**
	 * Fetches raw property data from the database
	 *
	 * @param string $identifier The node Identifier to fetch properties for
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function getRawPropertiesOfNode($identifier) {
		$statementHandle = $this->databaseHandle->prepare('SELECT "name", "value", "namespace", "multivalue", "type" FROM "properties" WHERE "parent" = ?');
		$statementHandle->execute(array($identifier));
		$properties = $statementHandle->fetchAll(PDO::FETCH_ASSOC);
		if (is_array($properties)) {
			foreach ($properties as &$property) {
				if($property['multivalue']) {
					$statementHandle = $this->databaseHandle->prepare('SELECT "index", "value" FROM "multivalueproperties" WHERE "parent" = ? AND "name" = ?');
					$statementHandle->execute(array($identifier, $property['name']));
					$multivalues = $statementHandle->fetchAll(PDO::FETCH_ASSOC);
					if (is_array($multivalues)) {
						$resultArray = array();
						foreach ($multivalues as $multivalue) {
							$resultArray[$multivalue['index']] = $multivalue['value'];
							$property['value'] = $resultArray;
						}
					}
				} else {
					$property['value'] = unserialize($property['value']);
				}
			}
		}
		return $properties;
	}

	/**
	 * Adds a property in the storage
	 *
	 * @param F3_PHPCR_PropertyInterface $property property to insert
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function addProperty(F3_PHPCR_PropertyInterface $property) {
		$this->databaseHandle->beginTransaction();

		$statementHandle = $this->databaseHandle->prepare('INSERT INTO "properties" ("parent", "name", "value", "namespace", "multivalue", "type") VALUES (?, ?, ?, \'\', ?, ?)');
		$statementHandle->execute(array(
			$property->getParent()->getIdentifier(),
			$property->getName(),
			($property->isMultiple() ? '' : $property->getSerializedValue()),
			(integer)$property->isMultiple(),
			$property->getType()
		));
		if ($property->isMultiple()) {
			foreach ($property->getValues() as $index => $value) {
				$statementHandle = $this->databaseHandle->prepare('INSERT INTO "multivalueproperties" ("parent", "name", "index", "value") VALUES (?, ?, ?, ?)');
				$statementHandle->execute(array(
					$property->getParent()->getIdentifier(),
					$property->getName(),
					$index,
					$value->getString()
				));
			}
		}

		$this->databaseHandle->commit();
	}

	/**
	 * Updates a property in the repository identified by identifier and name
	 *
	 * @param F3_PHPCR_PropertyInterface $property property to update
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function updateProperty(F3_PHPCR_PropertyInterface $property) {
		$this->databaseHandle->beginTransaction();

		$statementHandle = $this->databaseHandle->prepare('UPDATE "properties" SET "value"=?, "type"=? WHERE "parent"=? AND "name"=?');
		$statementHandle->execute(array(($property->isMultiple() ? '' : $property->getSerializedValue()), $property->getType(), $property->getParent()->getIdentifier(), $property->getName()));
		if ($property->isMultiple()) {
			$statementHandle = $this->databaseHandle->prepare('DELETE FROM "multivalueproperties" WHERE "parent"=? AND "name"=?');
			$statementHandle->execute(array(
				$property->getParent()->getIdentifier(),
				$property->getName()
			));
			foreach ($property->getValues() as $index => $value) {
				$statementHandle = $this->databaseHandle->prepare('INSERT INTO "multivalueproperties" ("parent", "name", "index", "value") VALUES (?, ?, ?, ?)');
				$statementHandle->execute(array(
					$property->getParent()->getIdentifier(),
					$property->getName(),
					$index,
					$value->getString()
				));
			}
		}

		$this->databaseHandle->commit();
	}

	/**
	 * Deletes a property in the repository identified by identifier and name
	 *
	 * @param F3_PHPCR_PropertyInterface $property property to remove
	 * @return void
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function removeProperty(F3_PHPCR_PropertyInterface $property) {
		$this->databaseHandle->beginTransaction();

		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "properties" WHERE "parent"=? AND "name"=?');
		$statementHandle->execute(array($property->getParent()->getIdentifier(), $property->getName()));
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "multivalueproperties" WHERE "parent"=? AND "name"=?');
		$statementHandle->execute(array($property->getParent()->getIdentifier(), $property->getName()));

		$this->databaseHandle->commit();
	}



	/**
	 * Fetches raw data for all nodetypes from the database
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRawNodeTypes() {
		try {
			$statementHandle = $this->databaseHandle->query('SELECT "name" FROM "nodetypes"');
			$nodetypes = $statementHandle->fetchAll(PDO::FETCH_ASSOC);
			return $nodetypes;
		} catch (PDOException $e) {
			throw new F3_TYPO3CR_StorageException('Could not read raw nodetypes. Make sure the database is initialized correctly. PDO error: ' . $e->getMessage(), 1216051821);
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
		$statementHandle = $this->databaseHandle->prepare('SELECT "name" FROM "nodetypes" WHERE "name" = ?');
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
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO "nodetypes" ("name") VALUES (?)');
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
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "nodetypes" WHERE "name"=?');
		$statementHandle->execute(array($name));
	}



	/**
	 * Fetches raw namespace data from the database
	 *
	 * @return array
	 * @author Sebastian Kurfürst <sebastian@ŧypo3.org>
	 */
	public function getRawNamespaces() {
		$statementHandle = $this->databaseHandle->query('SELECT "prefix", "uri" FROM "namespaces"');
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
		$statementHandle = $this->databaseHandle->prepare('UPDATE "namespaces" SET "prefix"=? WHERE "uri"=?');
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
		$statementHandle = $this->databaseHandle->prepare('UPDATE "namespaces" SET "uri"=? WHERE "prefix"=?');
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
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO "namespaces" ("prefix","uri") VALUES (?, ?)');
		$statementHandle->execute(array($prefix,$uri));
	}

	/**
	 * Deletes the namespace identified by $prefix.
	 *
	 * @param string $prefix The prefix of the namespace to delete
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function deleteNamespace($prefix) {
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "namespaces" WHERE "prefix"=?');
		$statementHandle->execute(array($prefix));
	}

}

?>
