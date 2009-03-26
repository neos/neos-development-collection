<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Storage\Backend;

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
 * @version $Id$
 */

/**
 * A Storage backend using PDO
 *
 * @package TYPO3CR
 * @subpackage Storage
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class PDO extends \F3\TYPO3CR\Storage\AbstractSQLBackend {

	/**
	 * @var \PDO
	 */
	protected $databaseHandle;

	/**
	 * @var string
	 */
	protected $PDODriver;

	/**
	 * Connect to the database
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function connect() {
		try {
			$splitdsn = explode(':', $this->dataSourceName, 2);
			$this->PDODriver = $splitdsn[0];

			if ($this->PDODriver === 'sqlite') {
				if (!file_exists($splitdsn[1])) {
					throw new \F3\TYPO3CR\StorageException('The configured SQLite database file (' . $splitdsn[1] . ') does not exist.', 1231177003);
				}
			}

			$this->databaseHandle = new \PDO($this->dataSourceName, $this->username, $this->password);
			$this->databaseHandle->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

			if ($this->PDODriver === 'mysql') {
				$this->databaseHandle->exec('SET SESSION sql_mode=\'ANSI\';');
			}
		} catch (\PDOException $e) {
			throw new \F3\TYPO3CR\StorageException('Could not connect to DSN "' . $this->dataSourceName . '". PDO error: ' . $e->getMessage(), 1219326502);
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
	 * Fetches raw node data of the root node of the current workspace.
	 *
	 * @return array|FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function getRawRootNode() {
		try {
			$statementHandle = $this->databaseHandle->prepare('SELECT "parent", "name", "namespace", "identifier", "nodetype", "nodetypenamespace" FROM "nodes" WHERE "parent" =\'\'');
			$statementHandle->execute();
			$result = $statementHandle->fetch(\PDO::FETCH_ASSOC);
			$result['name'] = $this->prefixName(array('namespaceURI' => $result['namespace'], 'name' => $result['name']));
			unset($result['namespace']);
			$result['nodetype'] = $this->prefixName(array('namespaceURI' => $result['nodetypenamespace'], 'name' => $result['nodetype']));
			unset($result['nodetypenamespace']);

			return $result;
		} catch (\PDOException $e) {
			throw new \F3\TYPO3CR\StorageException('Could not read raw root node. Make sure the database is initialized correctly. PDO error: ' . $e->getMessage(), 1216051737);
		}
	}

	/**
	 * Fetches raw node data from the database
	 *
	 * @param string $identifier The Identifier of the node to fetch
	 * @return array|FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function getRawNodeByIdentifier($identifier) {
		$statementHandle = $this->databaseHandle->prepare('SELECT "parent", "name", "namespace", "identifier", "nodetype", "nodetypenamespace" FROM "nodes" WHERE "identifier" = ?');
		$statementHandle->execute(array($identifier));
		$result = $statementHandle->fetch(\PDO::FETCH_ASSOC);
		if (is_array($result)) {
			$result['name'] = $this->prefixName(array('namespaceURI' => $result['namespace'], 'name' => $result['name']));
			unset($result['namespace']);
			$result['nodetype'] = $this->prefixName(array('namespaceURI' => $result['nodetypenamespace'], 'name' => $result['nodetype']));
			unset($result['nodetypenamespace']);
			return $result;
		}
		return FALSE;
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
		$rawNodes = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
		foreach ($rawNodes as $rawNode) {
			$nodeIdentifiers[] = $rawNode['identifier'];
		}
		return $nodeIdentifiers;
	}


	// nodetype related methods


	/**
	 * Fetches raw data for all nodetypes from the database
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function getRawNodeTypes() {
		try {
			$statementHandle = $this->databaseHandle->query('SELECT "name", "namespace" FROM "nodetypes"');
			$nodetypes = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
			foreach ($nodetypes as &$nodetype) {
				$nodetype['name'] = $this->prefixName(array('namespaceURI' => $nodetype['namespace'], 'name' => $nodetype['name']));
				unset($nodetype['namespace']);
			}
			return $nodetypes;
		} catch (\PDOException $e) {
			throw new \F3\TYPO3CR\StorageException('Could not read raw nodetypes. Make sure the database is initialized correctly (php index.php typo3cr setup database). PDO error: ' . $e->getMessage(), 1216051821);
		}
	}

	/**
	 * Fetches raw nodetype data from the database.
	 *
	 * Currently looks ridiculous, as it fetches only what we know already.
	 *
	 * @param string $nodeTypeNme The name of the nodetype record to fetch
	 * @return array|FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function getRawNodeType($nodeTypeName) {
		$splitName = $this->splitName($nodeTypeName);

		$statementHandle = $this->databaseHandle->prepare('SELECT "name", "namespace" FROM "nodetypes" WHERE "name" = ? AND "namespace" = ?');
		$statementHandle->execute(array($splitName['name'], $splitName['namespaceURI']));
		$nodetypes = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
		foreach ($nodetypes as &$nodetype) {
			$nodetype['name'] = $this->prefixName(array('namespaceURI' => $nodetype['namespace'], 'name' => $nodetype['name']));
			unset($nodetype['namespace']);
			return $nodetype;
		}
		return FALSE;
	}

	/**
	 * Adds the given nodetype to the database
	 *
	 * @param \F3\PHPCR\NodeType\NodeTypeDefinitionInterface $nodeTypeDefinition
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function addNodeType(\F3\PHPCR\NodeType\NodeTypeDefinitionInterface $nodeTypeDefinition) {
		$splitName = $this->splitName($nodeTypeDefinition->getName());

		$statementHandle = $this->databaseHandle->prepare('INSERT INTO "nodetypes" ("name","namespace") VALUES (?,?)');
		$statementHandle->execute(array($splitName['name'], $splitName['namespaceURI']));
	}

	/**
	 * Deletes the named nodetype from the database
	 *
	 * @param string $name
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function deleteNodeType($name) {
		$splitName = $this->splitName($name);

		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "nodetypes" WHERE "name"=? AND "namespace"=?');
		$statementHandle->execute(array($splitName['name'], $splitName['namespaceURI']));
	}


	// node related methods


	/**
	 * Adds a node to the storage
	 *
	 * @param \F3\PHPCR\NodeInterface $node node to insert
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function addNode(\F3\PHPCR\NodeInterface $node) {
		$splitNodeName = $this->splitName($node->getName());
		$splitNodeTypeName = $this->splitName($node->getPrimaryNodeType()->getName());

		$statementHandle = $this->databaseHandle->prepare('INSERT INTO "nodes" ("identifier", "parent", "nodetype", "nodetypenamespace", "name", "namespace") VALUES (?, ?, ?, ?, ?, ?)');
		$statementHandle->execute(array($node->getIdentifier(), $node->getParent()->getIdentifier(), $splitNodeTypeName['name'], $splitNodeTypeName['namespaceURI'], $splitNodeName['name'], $splitNodeName['namespaceURI']));
		$this->searchEngine->addNode($node);
	}

	/**
	 * Updates a node in the storage
	 *
	 * @param \F3\PHPCR\NodeInterface $node node to update
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function updateNode(\F3\PHPCR\NodeInterface $node) {
		if ($node->getDepth() > 0) {
			$splitNodeName = $this->splitName($node->getName());
			$splitNodeTypeName = $this->splitName($node->getPrimaryNodeType()->getName());

			$statementHandle = $this->databaseHandle->prepare('UPDATE "nodes" SET "parent"=?, "nodetype"=?, "nodetypenamespace"=?,"name"=?, "namespace"=? WHERE "identifier"=?');
			$statementHandle->execute(array($node->getParent()->getIdentifier(), $splitNodeTypeName['name'], $splitNodeTypeName['namespaceURI'], $splitNodeName['name'], $splitNodeName['namespaceURI'], $node->getIdentifier()));
			$this->searchEngine->updateNode($node);
		}
	}

	/**
	 * Deletes a node in the repository
	 *
	 * @param \F3\PHPCR\NodeInterface $node node to delete
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeNode(\F3\PHPCR\NodeInterface $node) {
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "nodes" WHERE "identifier"=?');
		$statementHandle->execute(array($node->getIdentifier()));
		$this->searchEngine->deleteNode($node);
	}


	// property related methods


	/**
	 * Adds a property in the storage
	 *
	 * @param \F3\PHPCR\PropertyInterface $property property to insert
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function addProperty(\F3\PHPCR\PropertyInterface $property) {
		$this->databaseHandle->beginTransaction();

		$splitName = $this->splitName($property->getName());

		$statementHandle = $this->databaseHandle->prepare('INSERT INTO "properties" ("parent", "name", "namespace", "multivalue", "type") VALUES (?, ?, ?, ?, ?)');
		$statementHandle->execute(array(
			$property->getParent()->getIdentifier(),
			$splitName['name'],
			$splitName['namespaceURI'],
			(integer)$property->isMultiple(),
			$property->getType()
		));

		$this->storePropertyValue($property);

		$this->databaseHandle->commit();
	}

	/**
	 * Updates a property in the repository identified by identifier and name
	 *
	 * @param \F3\PHPCR\PropertyInterface $property property to update
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function updateProperty(\F3\PHPCR\PropertyInterface $property) {
		$this->databaseHandle->beginTransaction();

		$splitName = $this->splitName($property->getName());

		$statementHandle = $this->databaseHandle->prepare('SELECT "multivalue", "type" FROM "properties" WHERE "parent"=? AND "name"=? AND "namespace"=?');
		$statementHandle->execute(array(
			$property->getParent()->getIdentifier(),
			$splitName['name'],
			$splitName['namespaceURI']
		));
		$rawProperties = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
			// We are using foreach here but it should only ever return 0 or 1 results (strictly speaking always 1 in "update"Property())
		foreach ($rawProperties as $rawProperty) {
			$typeName = \F3\PHP6\Functions::strtolower(\F3\PHPCR\PropertyType::nameFromValue($rawProperty['type']));
			$statementHandle = $this->databaseHandle->prepare('DELETE FROM "' . $typeName . 'properties" WHERE "parent"=? AND "name"=? AND "namespace"=?');
			$statementHandle->execute(array(
				$property->getParent()->getIdentifier(),
				$splitName['name'],
				$splitName['namespaceURI']
			));
		}

		$statementHandle = $this->databaseHandle->prepare('UPDATE "properties" SET "multivalue"=?, "type"=? WHERE "parent"=? AND "name"=? AND "namespace"=?');
		$statementHandle->execute(array(
			(integer)$property->isMultiple(),
			$property->getType(),
			$property->getParent()->getIdentifier(),
			$splitName['name'],
			$splitName['namespaceURI']
		));

		$this->storePropertyValue($property);

		$this->databaseHandle->commit();
	}

	/**
	 * Deletes a property in the repository identified by identifier and name
	 *
	 * @param \F3\PHPCR\PropertyInterface $property property to remove
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function removeProperty(\F3\PHPCR\PropertyInterface $property) {
		$this->databaseHandle->beginTransaction();

		$splitName = $this->splitName($property->getName());

		$statementHandle = $this->databaseHandle->prepare('SELECT "type" FROM "properties" WHERE "parent"=? AND "name"=? AND "namespace"=?');
		$statementHandle->execute(array(
			$property->getParent()->getIdentifier(),
			$splitName['name'],
			$splitName['namespaceURI']
		));
		$rawProperties = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
			// I am using foreach here but it should only ever return 0 or 1 results (strictly speaking always 1 in "update"Property())
		foreach ($rawProperties as $rawProperty) {
			$typeName = \F3\PHP6\Functions::strtolower(\F3\PHPCR\PropertyType::nameFromValue($rawProperty['type']));
			$statementHandle = $this->databaseHandle->prepare('DELETE FROM "' . $typeName . 'properties" WHERE "parent"=? AND "name"=? AND "namespace"=?');
			$statementHandle->execute(array(
				$property->getParent()->getIdentifier(),
				$splitName['name'],
				$splitName['namespaceURI']
			));
		}

		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "properties" WHERE "parent"=? AND "name"=? AND "namespace"=?');
		$statementHandle->execute(array($property->getParent()->getIdentifier(), $splitName['name'], $splitName['namespaceURI']));

		$this->databaseHandle->commit();
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
		$statementHandle = $this->databaseHandle->prepare('SELECT "parent", "name", "namespace", "multivalue", "type" FROM "properties" WHERE "parent" = ?');
		$statementHandle->execute(array($identifier));
		$properties = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
		return $this->getRawPropertyValues($properties);
	}

	/**
	 * Fetches raw properties with the given type and value from the database
	 *
	 * @param string $name name of the reference properties considered, if NULL properties of any name will be returned
	 * @param integer $type one of the types defined in \F3\PHPCR\PropertyType (does not work for path or name right now as those are represented by more than the value column in their respective tables)
	 * @param $value a value of the given type
	 * @return array
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function getRawPropertiesOfTypedValue($name, $type, $value) {
		$typeName = \F3\PHP6\Functions::strtolower(\F3\PHPCR\PropertyType::nameFromValue($type));

		if ($name == NULL) {
			$statementHandle = $this->databaseHandle->prepare('SELECT "properties"."parent", "properties"."name", "properties"."namespace", "properties"."multivalue", "properties"."type" FROM (SELECT DISTINCT "parent", "name", "namespace", "value" FROM "' . $typeName . 'properties") AS "pv" JOIN "properties" ON "pv"."parent" = "properties"."parent" AND "pv"."name" = "properties"."name" AND "pv"."namespace" = "properties"."namespace" WHERE "value" = ? ORDER BY "properties"."parent", "properties"."name", "properties"."namespace"');
			$statementHandle->execute(array($value));
		} else {
			$statementHandle = $this->databaseHandle->prepare('SELECT "properties"."parent", "properties"."name", "properties"."namespace", "properties"."multivalue", "properties"."type" FROM (SELECT DISTINCT "parent", "name", "namespace", "value" FROM "' . $typeName . 'properties") AS "pv" JOIN "properties" ON "pv"."parent" = "properties"."parent" AND "pv"."name" = "properties"."name" AND "pv"."namespace" = "properties"."namespace" WHERE "properties"."name" = ? AND "value" = ? ORDER BY "properties"."parent", "properties"."name", "properties"."namespace"');
			$statementHandle->execute(array($name, $value));
		}

		$properties = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
		return $this->getRawPropertyValues($properties);
	}


	// namespace related methods


	/**
	 * Fetches raw namespace data from the database
	 *
	 * @return array
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getRawNamespaces() {
		$statementHandle = $this->databaseHandle->query('SELECT "prefix", "uri" FROM "namespaces"');
		$namespaces = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
		return $namespaces;
	}

	/**
	 * Updates the prefix for the namespace identified by $uri
	 *
	 * @param string $prefix The prefix of the namespace to update
	 * @param string $uri The URI of the namespace to update
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function addNamespace($prefix, $uri) {
		$statementHandle = $this->databaseHandle->prepare('INSERT INTO "namespaces" ("prefix","uri") VALUES (?, ?)');
		$statementHandle->execute(array($prefix,$uri));
	}

	/**
	 * Deletes the namespace identified by $prefix.
	 *
	 * @param string $prefix The prefix of the namespace to delete
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function deleteNamespace($prefix) {
		$statementHandle = $this->databaseHandle->prepare('DELETE FROM "namespaces" WHERE "prefix"=?');
		$statementHandle->execute(array($prefix));
	}


	// various helper methods


	/**
	 * Converts the given string into the given type
	 *
	 * @param integer $type one of the constants defined in \F3\PHPCR\PropertyType
	 * @param string $string a string representing a value of the given type
	 *
	 * @return string|int|float|DateTime|boolean
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	protected function convertFromString($type, $string) {
		switch ($type) {
			case \F3\PHPCR\PropertyType::LONG:
				return (int) $string;
			case \F3\PHPCR\PropertyType::DOUBLE:
			case \F3\PHPCR\PropertyType::DECIMAL:
				return (float) $string;
			case \F3\PHPCR\PropertyType::DATE:
				return new \DateTime($string);
			case \F3\PHPCR\PropertyType::BOOLEAN:
				return (boolean) $string;
			default:
				return $string;
		}
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
	 * Returns TRUE of the node with the given identifier is a REFERENCE target
	 *
	 * @param string $identifier The UUID of the node to check for
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isReferenceTarget($identifier) {
		$statementHandle = $this->databaseHandle->prepare('SELECT COUNT(parent) FROM "referenceproperties" WHERE "value" = ?');
		$statementHandle->execute(array($identifier));
		$row = $statementHandle->fetch(\PDO::FETCH_NUM);

		return $row[0] > 0;
	}

	/**
	 * Splits the given name string into a namespace URI (using the namespaces table) and a name
	 *
	 * @param string $prefixedName the name in prefixed notation (':' between prefix if one exists and name, no ':' in string if there is no prefix)
	 * @return array (key "namespaceURI" for the namespace, "name" for the name)
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	protected function splitName($prefixedName) {
		$split = explode(':', $prefixedName, 2);

		if (count($split) != 2) {
			return array('namespaceURI' => '', 'name' => $prefixedName);
		}

		$namespacePrefix = $split[0];
		$name = $split[1];

		if ($this->namespaceRegistry) {
			return array('namespaceURI' => $this->namespaceRegistry->getURI($namespacePrefix), 'name' => $name);
		} else {
				// Fall back to namespaces table when no namespace registry is available

			$statementHandle = $this->databaseHandle->prepare('SELECT "uri" FROM "namespaces" WHERE "prefix"=?');
			$statementHandle->execute(array($namespacePrefix));
			$namespaces = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);

			if (count($namespaces) != 1) {
					// TODO: throw exception instead of returning once namespace table is properly filled
				return array('namespaceURI' => '', 'name' => $name);
			}
			foreach ($namespaces as $namespace) {
				return array('namespaceURI' => $namespace['uri'], 'name' => $name);
			}
		}
	}


	/**
	 * Takes the given array of a namespace URI (key 'namespaceURI' in the array) and name (key 'name') and converts it to a prefixed name
	 *
	 * @param array $namespacedName key 'namespaceURI' for the namespace, 'name' for the local name
	 * @return string
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	protected function prefixName($namespacedName) {
		if (! $namespacedName['namespaceURI']) {
			return $namespacedName['name'];
		}

		if ($this->namespaceRegistry) {
			return $this->namespaceRegistry->getPrefix($namespacedName['namespaceURI']) . ':' . $namespacedName['name'];
		} else {
				// Fall back to namespaces table when no namespace registry is available
			$statementHandle = $this->databaseHandle->prepare('SELECT "prefix" FROM "namespaces" WHERE "uri"=?');
			$statementHandle->execute(array($namespacedName['namespaceURI']));
			$namespaces = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);

			if (count($namespaces) != 1) {
					// TODO: throw exception instead of returning once namespace table is properly filled
				return $namespacedName['name'];
			}

			foreach ($namespaces as $namespace) {
				return $namespace['prefix'] . ':' . $namespacedName['name'];
			}
		}
	}


	// internal property related methods


	/**
	 * Adds a single valued property not of type \F3\PHPCR\PropertyType::PATH or \F3\PHPCR\PropertyType::NAME to the storage
	 *
	 * @param \F3\PHPCR\PropertyInterface $property
	 * @return void
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	protected function storePropertyValue(\F3\PHPCR\PropertyInterface $property) {
		switch ($property->getType()) {
			case \F3\PHPCR\PropertyType::PATH:
				$this->storePathProperty($property);
				return;
			break;
			case \F3\PHPCR\PropertyType::NAME:
				$this->storeNameProperty($property);
				return;
			break;
		}

		$splitName = $this->splitName($property->getName());
		$typeName = \F3\PHP6\Functions::strtolower(\F3\PHPCR\PropertyType::nameFromValue($property->getType()));

		if ($property->isMultiple()) {
			foreach ($property->getValues() as $index => $value) {
				$statementHandle = $this->databaseHandle->prepare('INSERT INTO "' . $typeName . 'properties" ("parent", "name", "namespace", "index", "value") VALUES (?, ?, ?, ?, ?)');
				$statementHandle->execute(array(
					$property->getParent()->getIdentifier(),
					$splitName['name'],
					$splitName['namespaceURI'],
					$index,
					$value->getString()
				));
			}
		} else {
			$statementHandle = $this->databaseHandle->prepare('INSERT INTO "' . $typeName . 'properties" ("parent", "name", "namespace", "value") VALUES (?, ?, ?, ?)');
			$statementHandle->execute(array(
				$property->getParent()->getIdentifier(),
				$splitName['name'],
				$splitName['namespaceURI'],
				$property->getValue()->getString()
			));
		}
	}


	/**
	 * Adds a single valued property of type \F3\PHPCR\PropertyType::PATH to the storage
	 *
	 * @param \F3\PHPCR\PropertyInterface $property
	 * @return void
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	protected function storePathProperty(\F3\PHPCR\PropertyInterface $property) {
		$splitName = $this->splitName($property->getName());

		if ($property->isMultiple()) {
			foreach ($property->getValues() as $index => $value) {
				$pathLevels = explode('/',$value->getString());
				$level = 0;
				foreach ($pathLevels as $pathLevel) {
					$splitPathLevel = $this->splitName($pathLevel);

					$statementHandle = $this->databaseHandle->prepare('INSERT INTO "pathproperties" ("parent", "name", "namespace", "index", "level", "value", "valuenamespace") VALUES (?, ?, ?, ?, ?, ?, ?)');
					$statementHandle->execute(array(
						$property->getParent()->getIdentifier(),
						$splitName['name'],
						$splitName['namespaceURI'],
						$index,
						$level,
						$splitPathLevel['name'],
						$splitPathLevel['namespaceURI']
					));
					$level++;
				}
			}
		} else {
			$pathLevels = explode('/',$property->getValue()->getString());
			$level = 0;
			foreach ($pathLevels as $pathLevel) {
				$splitPathLevel = $this->splitName($pathLevel);
				$statementHandle = $this->databaseHandle->prepare('INSERT INTO "pathproperties" ("parent", "name", "namespace", "level", "value", "valuenamespace") VALUES (?, ?, ?, ?, ?, ?)');
				$statementHandle->execute(array(
					$property->getParent()->getIdentifier(),
					$splitName['name'],
					$splitName['namespaceURI'],
					$level,
					$splitPathLevel['name'],
					$splitPathLevel['namespaceURI']
				));
				$level++;
			}
		}
	}

	/**
	 * Adds a single valued property of type \F3\PHPCR\PropertyType::NAME to the storage
	 *
	 * @param \F3\PHPCR\PropertyInterface $property
	 * @return void
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	protected function storeNameProperty(\F3\PHPCR\PropertyInterface $property) {
		$splitName = $this->splitName($property->getName());

		if ($property->isMultiple()) {
			foreach ($property->getValues() as $index => $value) {
				$splitValue = $this->splitName($value->getString());

				$statementHandle = $this->databaseHandle->prepare('INSERT INTO "nameproperties" ("parent", "name", "namespace", "index", "value", "valuenamespace") VALUES (?, ?, ?, ?, ?, ?)');
				$statementHandle->execute(array(
					$property->getParent()->getIdentifier(),
					$splitName['name'],
					$splitName['namespaceURI'],
					$index,
					$splitValue['name'],
					$splitValue['namespaceURI']
				));
			}
		} else {
			$splitValue = $this->splitName($property->getValue()->getString());
			$statementHandle = $this->databaseHandle->prepare('INSERT INTO "nameproperties" ("parent", "name", "namespace", "value", "valuenamespace") VALUES (?, ?, ?, ?, ?)');
			$statementHandle->execute(array(
				$property->getParent()->getIdentifier(),
				$splitName['name'],
				$splitName['namespaceURI'],
				$splitValue['name'],
				$splitValue['namespaceURI']
			));
		}
	}

	/**
	 * Fetches raw property values for the given properties from the typed tables in the database
	 *
	 * @param array $properties from the "properties" table (at least columns 'parent', 'name', 'namespace', 'type' and 'multivalue')
	 * @return array
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	protected function getRawPropertyValues($properties) {
		if (is_array($properties)) {
			foreach ($properties as &$property) {
				$property['multivalue'] = (boolean)$property['multivalue'];

				if (! $property['multivalue']) {
					if ($property['type'] == \F3\PHPCR\PropertyType::PATH) {
						$this->getRawSingleValuedPathProperty($property);
					} else {
						$this->getRawSingleValuedProperty($property);
					}
				} else {
					if ($property['type'] == \F3\PHPCR\PropertyType::PATH) {
						$this->getRawMultiValuedPathProperty($property);
					} else {
						$this->getRawMultiValuedProperty($property);
					}
				}

				$property['name'] = $this->prefixName(array('name' => $property['name'], 'namespaceURI' => $property['namespace']));
				unset($property['namespace']);
			}
		}
		return $properties;
	}

	/**
	 * Fetches raw single valued property of type \F3\PHPCR\PropertyType::PATH
	 *
	 * @param array &$property The property as read from the "properties" table of the database with $property['type'] == \F3\PHPCR\PropertyType::PATH and $property['multivalue'] == FALSE
	 * @return void
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	protected function getRawSingleValuedPathProperty(&$property) {
		$statementHandle = $this->databaseHandle->prepare('SELECT "value", "valuenamespace", "level" FROM "pathproperties" WHERE "parent" = ? AND "name" = ? AND "namespace" = ? ORDER BY "level"');
		$statementHandle->execute(array($property['parent'], $property['name'], $property['namespace']));
		$levels = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
		$result = array();
		foreach ($levels as $level) {
			$result[$level['level']] = $this->prefixName(array('namespaceURI' => $level['valuenamespace'], 'name' => $level['value']));
		}
		$property['value'] = implode('/', $result);
	}

	/**
	 * Fetches raw multi valued property of type \F3\PHPCR\PropertyType::PATH
	 *
	 * @param array &$property The property as read from the "properties" table of the database with $property['type'] == \F3\PHPCR\PropertyType::PATH and $property['multivalue'] == TRUE
	 * @return void
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	protected function getRawMultiValuedPathProperty(&$property) {
		$statementHandle = $this->databaseHandle->prepare('SELECT "value", "valuenamespace", "level", "index" FROM "pathproperties" WHERE "parent" = ? AND "name" = ? AND "namespace" = ? ORDER BY "index", "level"');
		$statementHandle->execute(array($property['parent'], $property['name'], $property['namespace']));
		$values = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
		$structuredValues=array();
		foreach ($values as $value) {
			$structuredValues[$value['index']]['index'] = $value['index'];
			$structuredValues[$value['index']][$value['level']]['level'] = $value['level'];
			$structuredValues[$value['index']][$value['level']]['value'] = $value['value'];
			$structuredValues[$value['index']][$value['level']]['valuenamespace'] = $value['valuenamespace'];
		}
		$property['value']=array();
		foreach ($structuredValues as $structuredValue) {
			$result = array();
			$index = $structuredValue['index'];
			unset($structuredValue['index']);
			foreach ($structuredValue as $level) {
				$result[$level['level']] = $this->prefixName(array('namespaceURI' => $level['valuenamespace'], 'name' => $level['value']));
			}
			$property['value'][$index] = implode('/', $result);
		}
	}

	/**
	 * Fetches raw single valued property not of type \F3\PHPCR\PropertyType::PATH
	 *
	 * @param array &$property The property as read from the "properties" table of the database with $property['type'] != \F3\PHPCR\PropertyType::PATH and $property['multivalue'] == FALSE
	 * @return void
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getRawSingleValuedProperty(&$property) {
		$typeName = \F3\PHP6\Functions::strtolower(\F3\PHPCR\PropertyType::nameFromValue($property['type']));

		$statementHandle = $this->databaseHandle->prepare('SELECT "value"' . ($property['type'] == \F3\PHPCR\PropertyType::NAME ? ',"valuenamespace"' : '') . ' FROM "' . $typeName . 'properties" WHERE "parent" = ? AND "name" = ? AND "namespace" = ?');
		$statementHandle->execute(array($property['parent'], $property['name'], $property['namespace']));
		$values = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
		foreach ($values as $value) {
			if ($property['type'] == \F3\PHPCR\PropertyType::NAME && $value['valuenamespace'] != '') {
				$property['value'] = $this->prefixName(array('namespaceURI' => $value['valuenamespace'], 'name' => $value['value']));
			} else {
				$property['value'] = $this->convertFromString($property['type'], $value['value']);
			}
		}
	}

	/**
	 * Fetches raw multi valued property not of type \F3\PHPCR\PropertyType::PATH
	 *
	 * @param array &$property The property as read from the "properties" table of the database with $property['type'] != \F3\PHPCR\PropertyType::PATH and $property['multivalue'] == TRUE
	 * @return void
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getRawMultiValuedProperty(&$property) {
		$typeName = \F3\PHP6\Functions::strtolower(\F3\PHPCR\PropertyType::nameFromValue($property['type']));

		$statementHandle = $this->databaseHandle->prepare('SELECT "index", "value"' . ($property['type'] == \F3\PHPCR\PropertyType::NAME ? ',"valuenamespace"' : '') . ' FROM "' . $typeName . 'properties" WHERE "parent" = ? AND "name" = ? AND "namespace" = ?');
		$statementHandle->execute(array($property['parent'], $property['name'], $property['namespace']));
		$multivalues = $statementHandle->fetchAll(\PDO::FETCH_ASSOC);
		if (is_array($multivalues)) {
			$resultArray = array();
			foreach ($multivalues as $multivalue) {

				if ($property['type'] == \F3\PHPCR\PropertyType::NAME && $multivalue['valuenamespace'] != '') {
					$resultArray[$multivalue['index']] = $this->prefixName(array('namespaceURI' => $multivalue['valuenamespace'], 'name' => $multivalue['value']));
				} else {
					$resultArray[$multivalue['index']] = $this->convertFromString($property['type'], $multivalue['value']);
				}

			}
			$property['value'] = $resultArray;
		}
	}

}

?>
