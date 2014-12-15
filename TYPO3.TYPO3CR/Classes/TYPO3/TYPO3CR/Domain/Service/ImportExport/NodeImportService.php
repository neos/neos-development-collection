<?php
namespace TYPO3\TYPO3CR\Domain\Service\ImportExport;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Utility\Algorithms;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\ImageVariant;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Exception\ImportException;

/**
 * Service for importing nodes from an XML structure into the content repository
 *
 * Internally, uses associative arrays instead of Domain Models for performance reasons, so "nodeData" in this
 * class is always an associative array.
 *
 * @Flow\Scope("singleton")
 */
class NodeImportService {

	const SUPPORTED_FORMAT_VERSION = '2.0';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related
	 * interface ...
	 *
	 * @Flow\Inject
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * @var ImportExportPropertyMappingConfiguration
	 */
	protected $propertyMappingConfiguration;

	/**
	 * @var array
	 */
	protected $nodeDataStack = array();

	/**
	 * @var array
	 */
	protected $nodeIdentifierStack = array();

	/**
	 * @var array
	 */
	protected $nodeNameStack;

	/**
	 * the list of property names of NodeData. These are the keys inside the nodeData array which is built as intermediate
	 * representation while parsing the XML.
	 *
	 * For each element, an array of additional settings can be specified; currently the only setting is the following:
	 *
	 * - columnType => \PDO::PARAM_*
	 *
	 * @var array
	 */
	protected $nodeDataPropertyNames = array(
		'Persistence_Object_Identifier' => array(),
		'identifier' => array(),
		'nodeType' => array(),
		'workspace' => array(),
		'sortingIndex' => array(),
		'version' => array(),
		'removed' => array(
			'columnType' => \PDO::PARAM_BOOL
		),
		'hidden' => array(
			'columnType' => \PDO::PARAM_BOOL
		),
		'hiddenInIndex' => array(
			'columnType' => \PDO::PARAM_BOOL
		),
		'path' => array(),
		'pathHash' => array(),
		'parentPath' => array(),
		'parentPathHash' => array(),
		'dimensionsHash' => array(),
		'dimensionValues' => array(),
		'properties' => array(),
		'accessRoles' => array()
	);

	/**
	 * Imports the sub-tree from the xml reader into the given target path.
	 *
	 * The root node of the imported tree becomes a child of the node specified as the target path,
	 * as the following example illustrates:
	 *
	 * 1. Existing Nodes Before Import:
	 *
	 *   path
	 *   - to
	 *   - - my
	 *   - - - targetNode
	 *   - - - - A
	 *   - other
	 *   - - nodes
	 *
	 * 2. Sub-tree in xml to import to 'path/to/my/targetNode':
	 *
	 *   <B>
	 *   - <B1/>
	 *   </B>
	 *
	 * 3. existing nodes after the import:
	 *
	 *   path
	 *   - to
	 *   - - my
	 *   - - - targetNode
	 *   - - - - A
	 *   - - - - B
	 *   - - - - - B1
	 *   - another
	 *   - - sub-tree
	 *
	 * @param \XMLReader $xmlReader The XML input to import - must be either XML as a string or a prepared \XMLReader instance containing XML data
	 * @param string $targetPath path to the node which becomes parent of the root of the imported sub-tree
	 * @param string $resourceLoadPath
	 * @throws \Exception
	 * @return void
	 */
	public function import(\XMLReader $xmlReader, $targetPath, $resourceLoadPath = NULL) {
		$this->propertyMappingConfiguration = new ImportExportPropertyMappingConfiguration($resourceLoadPath);
		$this->nodeNameStack = ($targetPath === '/') ? array() : explode('/', $targetPath);

		$formatVersion = $this->determineFormatVersion($xmlReader);
		switch ($formatVersion) {
			case self::SUPPORTED_FORMAT_VERSION:
				$this->importSubtree($xmlReader);
				break;
			case NULL:
				throw new ImportException('Failed to recognize format of the Node Data XML to import. Please make sure that you use a valid Node Data XML structure.', 1409059346);
			default:
				throw new ImportException('Failed to import Node Data XML: The format with version ' . $formatVersion . ' is not supported, only version ' . self::SUPPORTED_FORMAT_VERSION . ' is supported.', 1409059352);
		}
	}

	/**
	 * Determines the TYPO3CR format version of the given xml
	 *
	 * @param \XMLReader $xmlReader
	 * @return null|string the version as a string or null if the version could not be determined
	 */
	protected function determineFormatVersion(\XMLReader $xmlReader) {
		while ($xmlReader->nodeType !== \XMLReader::ELEMENT || $xmlReader->name !== 'nodes') {
			if (!$xmlReader->read()) {
				break;
			}
		}

		if ($xmlReader->name == 'nodes' && $xmlReader->nodeType == \XMLReader::ELEMENT) {
			return $xmlReader->getAttribute('formatVersion');
		}

		return FALSE;
	}

	/**
	 * Imports the sub-tree from the xml reader into the given target path.
	 * The root node of the imported tree becomes a child of the node specified by target path.
	 *
	 * This parser uses the depth-first reading strategy, which means it will read the input from top til bottom.
	 *
	 * @param \XMLReader $xmlReader A prepared XML Reader with the structure to import
	 * @return void
	 */
	protected function importSubtree(\XMLReader $xmlReader) {
		while ($xmlReader->read()) {
			switch ($xmlReader->nodeType) {
				case \XMLReader::ELEMENT:
					if (!$xmlReader->isEmptyElement) {
						$this->parseElement($xmlReader);
					}
					break;
				case \XMLReader::END_ELEMENT:
					if (!$xmlReader->name === 'nodes') {
						return; // all done, reached the closing </nodes> tag
					}
					$this->parseEndElement($xmlReader);
					break;
			}
		}
	}

	/**
	 * Parses the given XML element and adds its content to the internal content tree
	 *
	 * @param \XMLReader $xmlReader The XML Reader with the element to be parsed as its root
	 * @return void
	 */
	protected function parseElement(\XMLReader $xmlReader) {
		$elementName = $xmlReader->name;
		switch ($elementName) {
			case 'node':
				// update current node identifier
				$this->nodeIdentifierStack[] = $xmlReader->getAttribute('identifier');
				// update current path
				$nodeName = $xmlReader->getAttribute('nodeName');
				if ($nodeName != '/') {
					$this->nodeNameStack[] = $nodeName;
				}
				break;
			case 'variant':
				$path = $this->getCurrentPath();
				$parentPath = $this->getParentPath($path);

				$currentNodeIdentifier = $this->nodeIdentifierStack[count($this->nodeIdentifierStack) - 1];
				$this->nodeDataStack[] = array(
					'Persistence_Object_Identifier' => Algorithms::generateUUID(),
					'identifier' => $currentNodeIdentifier,
					'nodeType' => $xmlReader->getAttribute('nodeType'),
					'workspace' => $xmlReader->getAttribute('workspace'),
					'sortingIndex' => $xmlReader->getAttribute('sortingIndex'),
					'version' => $xmlReader->getAttribute('version'),
					'removed' => (boolean)$xmlReader->getAttribute('removed'),
					'hidden' => (boolean)$xmlReader->getAttribute('hidden'),
					'hiddenInIndex' => (boolean)$xmlReader->getAttribute('hiddenInIndex'),
					'path' => $path,
					'pathHash' => md5($path),
					'parentPath' => $parentPath,
					'parentPathHash' => md5($parentPath),
					'properties' => array(),
					'accessRoles' => array(),
					'dimensionValues' => array() // is post-processed before save in END_ELEMENT-case
				);
				break;
			case 'dimensions':
				$this->nodeDataStack[count($this->nodeDataStack) - 1]['dimensionValues'] = $this->parseDimensionsElement($xmlReader);
				break;
			case 'properties':
				$this->nodeDataStack[count($this->nodeDataStack) - 1]['properties'] = $this->parsePropertiesElement($xmlReader);
				break;
			case 'accessRoles':
				$this->nodeDataStack[count($this->nodeDataStack) - 1]['accessRoles'] = json_decode($xmlReader->readString());
				break;
			default:
				$value = $xmlReader->readString();
				$this->nodeDataStack[count($this->nodeDataStack) - 1][$elementName] = $value;
				break;
		}
	}

	/**
	 * Parses the content of the dimensions-tag and returns the dimensions as an array
	 * 'dimension name' => dimension value
	 *
	 * @param \XMLReader $reader reader positioned just after an opening dimensions-tag
	 * @return array the dimension values
	 */
	protected function parseDimensionsElement(\XMLReader $reader) {
		$dimensions = array();
		$currentDimension = NULL;

		while ($reader->read()) {
			switch ($reader->nodeType) {
				case \XMLReader::ELEMENT:
					$currentDimension = $reader->name;
					break;
				case \XMLReader::END_ELEMENT:
					if ($reader->name == 'dimensions') {
						return $dimensions;
					}
					break;
				case \XMLReader::CDATA:
				case \XMLReader::TEXT:
					$dimensions[$currentDimension][] = $reader->value;
					break;
			}
		}

		return $dimensions;
	}

	/**
	 * Parses the content of exported array and returns the values
	 *
	 * @param \XMLReader $reader reader positioned just after an opening array-tag
	 * @return array the array values
	 */
	protected function parseArrayElements(\XMLReader $reader) {
		$values = array();
		$currentKey = NULL;
		$depth = 0;
		$currentEncoding = NULL;
		$currentClassName = NULL;
		$currentIdentifier = NULL;

		do {
			switch ($reader->nodeType) {
				case \XMLReader::ELEMENT:
					$depth++;
					// __type="object" __identifier="uuid goes here" __classname="TYPO3\Media\Domain\Model\ImageVariant" __encoding="json"
					$currentType = $reader->getAttribute('__type');
					$currentIdentifier = $reader->getAttribute('__identifier');
					$currentClassName = $reader->getAttribute('__classname');
					$currentEncoding = $reader->getAttribute('__encoding');
					break;
				case \XMLReader::END_ELEMENT:
					if ($depth === 0) {
						return $values;
					} else {
						$depth--;
					}
					break;
				case \XMLReader::CDATA:
				case \XMLReader::TEXT:
					$values[] = $this->convertElementToValue($reader, $currentType, $currentEncoding, $currentClassName, $currentIdentifier);
					break;
			}
		} while  ($reader->read());
	}

	/**
	 * Parses the content of the properties-tag and returns the properties as an array
	 * 'property name' => property value
	 *
	 * @param \XMLReader $reader reader positioned just after an opening properties-tag
	 * @return array the properties
	 * @throws \Exception
	 */
	protected function parsePropertiesElement(\XMLReader $reader) {
		$properties = array();
		$currentProperty = NULL;
		$currentType = NULL;
		$currentEncoding = NULL;
		$currentClassName = NULL;
		$currentIdentifier = NULL;

		while ($reader->read()) {
			switch ($reader->nodeType) {
				case \XMLReader::ELEMENT:
					// __type="object" __identifier="uuid goes here" __classname="TYPO3\Media\Domain\Model\ImageVariant" __encoding="json"
					if ($currentType === 'array') {
						$value = $this->parseArrayElements($reader);
						$properties[$currentProperty] = $value;
					}

					$currentProperty = $reader->name;
					$currentType = $reader->getAttribute('__type');
					$currentIdentifier = $reader->getAttribute('__identifier');
					$currentClassName = $reader->getAttribute('__classname');
					$currentEncoding = $reader->getAttribute('__encoding');

					if ($reader->isEmptyElement) {
						$currentType = NULL;
					}
					break;
				case \XMLReader::END_ELEMENT:
					if ($reader->name == 'properties') {
						return $properties;
					}
					break;
				case \XMLReader::CDATA:
				case \XMLReader::TEXT:
					$properties[$currentProperty] = $this->convertElementToValue($reader, $currentType, $currentEncoding, $currentClassName, $currentIdentifier);
					break;
			}
		}

		return $properties;
	}

	/**
	 * Convert an element to the value it represents.
	 *
	 * @param \XMLReader $reader
	 * @param string $currentType current element (userland) type
	 * @param string $currentEncoding date encoding of element
	 * @param string $currentClassName class name of element
	 * @param string $currentIdentifier identifier of element
	 * @return mixed
	 * @throws \Exception
	 * @throws \TYPO3\Flow\Property\Exception
	 * @throws \TYPO3\Flow\Security\Exception
	 */
	protected function convertElementToValue(\XMLReader $reader, $currentType, $currentEncoding, $currentClassName, $currentIdentifier = '') {
		switch ($currentType) {
			case 'object':
				if ($currentClassName === 'DateTime') {
					$value = $this->propertyMapper->convert($reader->value, $currentClassName, $this->propertyMappingConfiguration);
				} elseif ($currentEncoding === 'json') {
					$value = $this->propertyMapper->convert(json_decode($reader->value, TRUE), $currentClassName, $this->propertyMappingConfiguration);
				} else {
					throw new \Exception(sprintf('Unsupported encoding "%s"', $currentEncoding), 1404397061);
				}
				break;
			case 'string':
				$value = $reader->value;
				break;
			default:
				$value = $this->propertyMapper->convert($reader->value, $currentType, $this->propertyMappingConfiguration);

				return $value;
		}

		return $value;
	}

	/**
	 * Parses the closing tags writes data to the database then
	 *
	 * @param \XMLReader $reader
	 * @return void
	 */
	protected function parseEndElement(\XMLReader $reader) {
		switch ($reader->name) {
			case 'node':
				// update current path
				array_pop($this->nodeNameStack);
				// update current node identifier
				array_pop($this->nodeIdentifierStack);
				break;
			case 'variant':
				// we have collected all data for the node so we save it
				$nodeData = array_pop($this->nodeDataStack);

				// if XML files lack the identifier for a node, add it here
				if (!isset($nodeData['identifier'])) {
					$nodeData['identifier'] = Algorithms::generateUUID();
				}

				$this->persistNodeData($nodeData);
				break;
		}
	}

	/**
	 * provides the path for a NodeData according to the current stacks
	 *
	 * @return string
	 */
	protected function getCurrentPath() {
		$path = join('/', $this->nodeNameStack);
		if ($path == '') {
			$path = '/';

			return $path;
		}

		return $path;
	}

	/**
	 * provides the parent of the given path
	 *
	 * @param string $path path to get parent for
	 * @return string parent path
	 */
	protected function getParentPath($path) {
		if ($path == '/') {
			return '';
		}
		if ($path != '/') {
			$endIndex = strrpos($path, '/');
			$index = strpos($path, '/');
			// path is something like /nodeInRootSpace
			if ($index == $endIndex) {
				return '/';
			} else { // node is something like /node/not/in/root/space
				return substr($path, 0, $endIndex);
			}
		}
	}

	/**
	 * Saves the given array as a node data entity without using the ORM.
	 *
	 * If the node data already exists (same dimensions, same identifier, same workspace)
	 * it is replaced.
	 *
	 * @param array $nodeData node data to save as an associative array ( $column_name => $value )
	 * @throws \TYPO3\TYPO3CR\Exception\ImportException
	 * @return void
	 */
	protected function persistNodeData($nodeData) {
		if ($nodeData['workspace'] != 'live') {
			throw new ImportException('Saving NodeData with workspace != "live" using direct SQL not supported yet. Workspace is "' . $nodeData['workspace'] . '".');
		}
		if ($nodeData['path'] === '/') {
			return;
		}

		// cleanup old data
		/** @var \Doctrine\DBAL\Connection $connection */
		$connection = $this->entityManager->getConnection();

		// prepare node dimensions
		$dimensionValues = $nodeData['dimensionValues'];
		$dimensionsHash = NodeData::sortDimensionValueArrayAndReturnDimensionsHash($dimensionValues);

		$objectArrayDataTypeHandler = \TYPO3\Flow\Persistence\Doctrine\DataTypes\ObjectArray::getType(\TYPO3\Flow\Persistence\Doctrine\DataTypes\ObjectArray::OBJECTARRAY);

		// post-process node data
		$nodeData['dimensionsHash'] = $dimensionsHash;
		$nodeData['dimensionValues'] = $objectArrayDataTypeHandler->convertToDatabaseValue($dimensionValues, $connection->getDatabasePlatform());
		$nodeData['properties'] = $objectArrayDataTypeHandler->convertToDatabaseValue($nodeData['properties'], $connection->getDatabasePlatform());
		$nodeData['accessRoles'] = serialize($nodeData['accessRoles']);


		$connection->prepare('DELETE FROM typo3_typo3cr_domain_model_nodedimension'
			. ' WHERE nodedata IN ('
			. '   SELECT persistence_object_identifier FROM typo3_typo3cr_domain_model_nodedata'
			. '   WHERE identifier = :identifier'
			. '   AND workspace = :workspace'
			. '   AND dimensionshash = :dimensionsHash'
			. ' )'
		)->execute(array(
			'identifier' => $nodeData['identifier'],
			'workspace' => $nodeData['workspace'],
			'dimensionsHash' => $nodeData['dimensionsHash']
		));

		/** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
		$queryBuilder = $this->entityManager->createQueryBuilder();
		$queryBuilder
			->delete()
			->from('TYPO3\TYPO3CR\Domain\Model\NodeData', 'n')
			->where('n.identifier = :identifier')
			->andWhere('n.dimensionsHash = :dimensionsHash')
			->andWhere('n.workspace = :workspace')
			->setParameter('identifier', $nodeData['identifier'])
			->setParameter('workspace', $nodeData['workspace'])
			->setParameter('dimensionsHash', $nodeData['dimensionsHash']);
		$queryBuilder->getQuery()->execute();

		// insert new data
		// we need to use executeUpdate to execute the INSERT -- else the data types are not taken into account.
		// That's why we build a DQL INSERT statement which is then executed.
		$queryParts = array();
		$queryArguments = array();
		$queryTypes = array();
		foreach ($this->nodeDataPropertyNames as $propertyName => $propertyConfig) {
			$queryParts[$propertyName] = ':' . $propertyName;
			$queryArguments[$propertyName] = $nodeData[$propertyName];
			if (isset($propertyConfig['columnType'])) {
				$queryTypes[$propertyName] = $propertyConfig['columnType'];
			}
		}
		$connection->executeUpdate('INSERT INTO typo3_typo3cr_domain_model_nodedata (' . implode(', ', array_keys($queryParts)) . ') VALUES (' . implode(', ', $queryParts) . ')', $queryArguments, $queryTypes);

		foreach ($dimensionValues as $dimension => $values) {
			foreach ($values as $value) {
				$nodeDimension = array(
					'persistence_object_identifier' => Algorithms::generateUUID(),
					'nodedata' => $nodeData['Persistence_Object_Identifier'],
					'name' => $dimension,
					'value' => $value
				);
				$connection->insert('typo3_typo3cr_domain_model_nodedimension', $nodeDimension);
			}
		}
	}
}
