<?php
namespace Neos\ContentRepository\Domain\Service\ImportExport;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Aspect\PersistenceMagicInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Persistence\Doctrine\DataTypes\JsonArrayType;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Security\Context;
use Neos\Flow\Utility\Algorithms;
use Neos\Flow\Utility\Now;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Exception\ImportException;
use Neos\ContentRepository\Utility;

/**
 * Service for importing nodes from an XML structure into the content repository
 *
 * Internally, uses associative arrays instead of Domain Models for performance reasons, so "nodeData" in this
 * class is always an associative array.
 *
 * @Flow\Scope("singleton")
 */
class NodeImportService
{
    const SUPPORTED_FORMAT_VERSION = '2.0';

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * Doctrine's Entity Manager.
     *
     * @Flow\Inject
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @var ImportExportPropertyMappingConfiguration
     */
    protected $propertyMappingConfiguration;

    /**
     * @var array
     */
    protected $nodeDataStack = [];

    /**
     * @var array
     */
    protected $nodeIdentifierStack = [];

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
    protected $nodeDataPropertyNames = [
        'Persistence_Object_Identifier' => [],
        'identifier' => [],
        'nodeType' => [],
        'workspace' => [],
        'sortingIndex' => [],
        'version' => [],
        'removed' => [
            'columnType' => \PDO::PARAM_BOOL
        ],
        'hidden' => [
            'columnType' => \PDO::PARAM_BOOL
        ],
        'hiddenInIndex' => [
            'columnType' => \PDO::PARAM_BOOL
        ],
        'path' => [],
        'pathHash' => [],
        'parentPath' => [],
        'parentPathHash' => [],
        'dimensionsHash' => [],
        'dimensionValues' => [],
        'properties' => [],
        'hiddenBeforeDateTime' => [
            'columnType' => Type::DATETIME
        ],
        'hiddenAfterDateTime' => [
            'columnType' => Type::DATETIME
        ],
        'creationDateTime' => [
            'columnType' => Type::DATETIME
        ],
        'lastModificationDateTime' => [
            'columnType' => Type::DATETIME
        ],
        'lastPublicationDateTime' => [
            'columnType' => Type::DATETIME
        ],
        'accessRoles' => []
    ];

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
     * @throws ImportException
     * @return void
     */
    public function import(\XMLReader $xmlReader, $targetPath, $resourceLoadPath = null)
    {
        $this->propertyMappingConfiguration = new ImportExportPropertyMappingConfiguration($resourceLoadPath);
        $this->nodeNameStack = [];

        if ($targetPath !== '/') {
            $pathSegments = explode('/', $targetPath);
            array_shift($pathSegments);
            $this->nodeNameStack = array_map(function ($pathSegment) {
                return Utility::renderValidNodeName($pathSegment);
            }, $pathSegments);
        }

        $formatVersion = $this->determineFormatVersion($xmlReader);
        switch ($formatVersion) {
            case self::SUPPORTED_FORMAT_VERSION:
                $this->securityContext->withoutAuthorizationChecks(function () use ($xmlReader) {
                    $this->importSubtree($xmlReader);
                });
                break;
            case null:
                throw new ImportException('Failed to recognize format of the Node Data XML to import. Please make sure that you use a valid Node Data XML structure.', 1409059346);
            default:
                throw new ImportException('Failed to import Node Data XML: The format with version ' . $formatVersion . ' is not supported, only version ' . self::SUPPORTED_FORMAT_VERSION . ' is supported.', 1409059352);
        }
    }

    /**
     * Determines the ContentRepository format version of the given xml
     *
     * @param \XMLReader $xmlReader
     * @return null|string the version as a string or null if the version could not be determined
     */
    protected function determineFormatVersion(\XMLReader $xmlReader)
    {
        while ($xmlReader->nodeType !== \XMLReader::ELEMENT || $xmlReader->name !== 'nodes') {
            if (!$xmlReader->read()) {
                break;
            }
        }

        if ($xmlReader->name === 'nodes' && $xmlReader->nodeType === \XMLReader::ELEMENT) {
            return $xmlReader->getAttribute('formatVersion');
        }

        return false;
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
    protected function importSubtree(\XMLReader $xmlReader)
    {
        while ($xmlReader->read()) {
            if ($xmlReader->nodeType === \XMLReader::COMMENT) {
                continue;
            }

            switch ($xmlReader->nodeType) {
                case \XMLReader::ELEMENT:
                    if (!$xmlReader->isEmptyElement) {
                        $this->parseElement($xmlReader);
                    }
                    break;
                case \XMLReader::END_ELEMENT:
                    if ((string)$xmlReader->name === 'nodes') {
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
     * @throws ImportException
     */
    protected function parseElement(\XMLReader $xmlReader)
    {
        $elementName = $xmlReader->name;
        switch ($elementName) {
            case 'node':
                // update current node identifier
                $this->nodeIdentifierStack[] = $xmlReader->getAttribute('identifier');
                // update current path
                $nodeName = $xmlReader->getAttribute('nodeName');
                if ($nodeName !== '/' && $nodeName !== '') {
                    $this->nodeNameStack[] = Utility::renderValidNodeName($nodeName);
                }
                break;
            case 'variant':
                $path = $this->getCurrentPath();
                $parentPath = $this->getParentPath($path);

                $now = new Now();
                $currentNodeIdentifier = $this->nodeIdentifierStack[count($this->nodeIdentifierStack) - 1];
                $this->nodeDataStack[] = [
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
                    'properties' => [],
                    'accessRoles' => [],
                    'creationDateTime' => $now,
                    'lastModificationDateTime' => $now,
                    'dimensionValues' => [] // is post-processed before save in END_ELEMENT-case
                ];
                break;
            case 'dimensions':
                $this->nodeDataStack[count($this->nodeDataStack) - 1]['dimensionValues'] = $this->parseDimensionsElement($xmlReader);
                break;
            case 'properties':
                $currentNodeIdentifier = $this->nodeDataStack[count($this->nodeDataStack) - 1]['identifier'];
                $this->nodeDataStack[count($this->nodeDataStack) - 1][$elementName] = $this->parsePropertiesElement($xmlReader, $currentNodeIdentifier);
                break;
            case 'accessRoles':
                $currentNodeIdentifier = $this->nodeDataStack[count($this->nodeDataStack) - 1]['identifier'];
                $this->nodeDataStack[count($this->nodeDataStack) - 1][$elementName] = $this->parseArrayElements($xmlReader, 'accessRoles', $currentNodeIdentifier);
                break;
            case 'hiddenBeforeDateTime':
            case 'hiddenAfterDateTime':
            case 'creationDateTime':
            case 'lastModificationDateTime':
            case 'lastPublicationDateTime':
                $stringValue = trim($xmlReader->readString());
                $dateValue = $this->propertyMapper->convert($stringValue, 'DateTime', $this->propertyMappingConfiguration);
                $this->nodeDataStack[count($this->nodeDataStack) - 1][$elementName] = $dateValue;
                break;
            default:
                throw new ImportException(sprintf('Unexpected element <%s> ', $elementName), 1423578065);
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
    protected function parseDimensionsElement(\XMLReader $reader)
    {
        $dimensions = [];
        $currentDimension = null;

        while ($reader->read()) {
            switch ($reader->nodeType) {
                case \XMLReader::ELEMENT:
                    $currentDimension = $reader->name;
                    break;
                case \XMLReader::END_ELEMENT:
                    if ($reader->name === 'dimensions') {
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
     * @param string $elementName
     * @param string $currentNodeIdentifier
     * @return array the array values
     */
    protected function parseArrayElements(\XMLReader $reader, $elementName, $currentNodeIdentifier)
    {
        $values = [];
        $depth = 0;

        // The following silences static code analysis warnings about undefined variables.
        // during runtime this doesn't happen, because the $reader must be at an ELEMENT,
        // thus the variables would be defined in the first case block before they can be
        // used.
        $currentType = null;
        $currentEncoding = null;
        $currentClassName = null;
        $currentIdentifier = null;

        do {
            switch ($reader->nodeType) {
                case \XMLReader::ELEMENT:
                    $depth++;
                    // __type="object" __classname="Neos\Media\Domain\Model\ImageVariant" __encoding="json"
                    $currentType = $reader->getAttribute('__type');
                    $currentClassName = $reader->getAttribute('__classname');
                    $currentEncoding = $reader->getAttribute('__encoding');
                    break;
                case \XMLReader::END_ELEMENT:
                    if ($reader->name === $elementName) {
                        return $values;
                    }
                    break;
                case \XMLReader::CDATA:
                case \XMLReader::TEXT:
                    $values[] = $this->convertElementToValue($reader, $currentType, $currentEncoding, $currentClassName, $currentNodeIdentifier, $elementName);
                    break;
            }
        } while ($reader->read());
    }

    /**
     * Parses the content of the properties-tag and returns the properties as an array
     * 'property name' => property value
     *
     * @param \XMLReader $reader reader positioned just after an opening properties-tag
     * @param string $currentNodeIdentifier
     * @return array the properties
     */
    protected function parsePropertiesElement(\XMLReader $reader, $currentNodeIdentifier)
    {
        $properties = [];
        $currentProperty = null;
        $currentType = null;
        $currentEncoding = null;
        $currentClassName = null;
        $currentIdentifier = null;

        while ($reader->read()) {
            switch ($reader->nodeType) {
                case \XMLReader::ELEMENT:
                    $currentProperty = $reader->name;
                    $currentType = $reader->getAttribute('__type');
                    $currentIdentifier = $reader->getAttribute('__identifier');
                    $currentClassName = $reader->getAttribute('__classname');
                    $currentEncoding = $reader->getAttribute('__encoding');

                    if ($reader->isEmptyElement) {
                        switch ($currentType) {
                            case 'array':
                                $properties[$currentProperty] = [];
                                break;
                            case 'string':
                                $properties[$currentProperty] = '';
                                break;
                            default:
                                $properties[$currentProperty] = null;
                        }
                        $currentType = null;
                    }

                    // __type="object" __identifier="uuid goes here" __classname="Neos\Media\Domain\Model\ImageVariant" __encoding="json"
                    if ($currentType === 'array') {
                        $value = $this->parseArrayElements($reader, $currentProperty, $currentNodeIdentifier);
                        $properties[$currentProperty] = $value;
                    }
                    break;
                case \XMLReader::END_ELEMENT:
                    if ($reader->name === 'properties') {
                        return $properties;
                    }
                    break;
                case \XMLReader::CDATA:
                case \XMLReader::TEXT:
                    $properties[$currentProperty] = $this->convertElementToValue($reader, $currentType, $currentEncoding, $currentClassName, $currentNodeIdentifier, $currentProperty);
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
     * @param string $currentNodeIdentifier identifier of the node
     * @param string $currentProperty current property name
     * @return mixed
     * @throws ImportException
     */
    protected function convertElementToValue(\XMLReader $reader, $currentType, $currentEncoding, $currentClassName, $currentNodeIdentifier, $currentProperty)
    {
        switch ($currentType) {
            case 'object':
                if ($currentClassName === 'DateTime') {
                    $stringValue = trim($reader->value);
                    $value = $this->propertyMapper->convert($stringValue, $currentClassName, $this->propertyMappingConfiguration);
                    if ($this->propertyMapper->getMessages()->hasErrors()) {
                        throw new ImportException(sprintf('Could not convert element <%s> to DateTime for node %s', $currentProperty, $currentNodeIdentifier), 1472992032);
                    }
                } elseif ($currentEncoding === 'json') {
                    $decodedJson = json_decode($reader->value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new ImportException(sprintf('Could not parse encoded JSON in element <%s> for node %s: %s', $currentProperty, $currentNodeIdentifier, json_last_error_msg()), 1472992033);
                    }
                    $value = $this->propertyMapper->convert($decodedJson, $currentClassName, $this->propertyMappingConfiguration);
                    if ($this->propertyMapper->getMessages()->hasErrors()) {
                        throw new ImportException(sprintf('Could not convert element <%s> to %s for node %s', $currentProperty, $currentClassName, $currentNodeIdentifier), 1472992034);
                    }
                } else {
                    throw new ImportException(sprintf('Unsupported encoding "%s"', $currentEncoding), 1404397061);
                }
                break;
            case 'string':
                $value = $reader->value;
                break;
            default:
                $value = $this->propertyMapper->convert($reader->value, $currentType, $this->propertyMappingConfiguration);
                if ($this->propertyMapper->getMessages()->hasErrors()) {
                    throw new ImportException(sprintf('Could not convert element <%s> to %s for node %s', $currentProperty, $currentType, $currentNodeIdentifier), 1472992035);
                }
                return $value;
        }

        $this->persistEntities($value);
        return $value;
    }

    /**
     * Checks if a propertyValue contains an entity and persists it.
     *
     * @param mixed $propertyValue
     * @return void
     */
    protected function persistEntities($propertyValue)
    {
        if (!$propertyValue instanceof \Iterator && !is_array($propertyValue)) {
            $propertyValue = [$propertyValue];
        }
        foreach ($propertyValue as $possibleEntity) {
            if (is_object($possibleEntity) && $possibleEntity instanceof PersistenceMagicInterface) {
                $this->persistenceManager->isNewObject($possibleEntity) ? $this->persistenceManager->add($possibleEntity) : $this->persistenceManager->update($possibleEntity);

                // TODO: Needed because the originalAsset will not cascade persist. We should find a generic solution to this.
                if ($possibleEntity instanceof ImageVariant) {
                    $asset = $possibleEntity->getOriginalAsset();
                    $this->persistenceManager->isNewObject($asset) ? $this->persistenceManager->add($asset) : $this->persistenceManager->update($asset);
                }
            }
        }
    }

    /**
     * Parses the closing tags writes data to the database then
     *
     * @param \XMLReader $reader
     * @return void
     * @throws ImportException
     */
    protected function parseEndElement(\XMLReader $reader)
    {
        switch ($reader->name) {
            case 'hiddenBeforeDateTime':
            case 'hiddenAfterDateTime':
            case 'creationDateTime':
            case 'lastModificationDateTime':
            case 'lastPublicationDateTime':
            case 'accessRoles':
                break;
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
            default:
                throw new ImportException(sprintf('Unexpected end element <%s> ', $reader->name), 1423578066);
                break;
        }
    }

    /**
     * Provides the path for a NodeData according to the current stacks
     *
     * @return string
     */
    protected function getCurrentPath()
    {
        $path = implode('/', $this->nodeNameStack);
        return '/' . $path;
    }

    /**
     * Provides the parent of the given path
     *
     * @param string $path path to get parent for
     * @return string parent path
     */
    protected function getParentPath($path)
    {
        if ($path === '/') {
            return '';
        }
        $endIndex = strrpos($path, '/');
        $index = strpos($path, '/');
        // path is something like /nodeInRootSpace
        if ($index === $endIndex) {
            return '/';
        } else { // node is something like /node/not/in/root/space
            return substr($path, 0, $endIndex);
        }
    }

    /**
     * Saves the given array as a node data entity without using the ORM.
     *
     * If the node data already exists (same dimensions, same identifier, same workspace)
     * it is replaced.
     *
     * @param array $nodeData node data to save as an associative array ( $column_name => $value )
     * @throws ImportException
     * @return void
     */
    protected function persistNodeData($nodeData)
    {
        if ($nodeData['workspace'] !== 'live') {
            throw new ImportException('Saving NodeData with workspace != "live" using direct SQL not supported yet. Workspace is "' . $nodeData['workspace'] . '".');
        }
        if ($nodeData['path'] === '/') {
            return;
        }

        // cleanup old data
        /** @var Connection $connection */
        $connection = $this->entityManager->getConnection();

        // prepare node dimensions
        $dimensionValues = $nodeData['dimensionValues'];
        $dimensionsHash = Utility::sortDimensionValueArrayAndReturnDimensionsHash($dimensionValues);

        $jsonPropertiesDataTypeHandler = JsonArrayType::getType(JsonArrayType::FLOW_JSON_ARRAY);

        // post-process node data
        $nodeData['dimensionsHash'] = $dimensionsHash;
        $nodeData['dimensionValues'] = $jsonPropertiesDataTypeHandler->convertToDatabaseValue($dimensionValues, $connection->getDatabasePlatform());
        $nodeData['properties'] = $jsonPropertiesDataTypeHandler->convertToDatabaseValue($nodeData['properties'], $connection->getDatabasePlatform());
        $nodeData['accessRoles'] = $jsonPropertiesDataTypeHandler->convertToDatabaseValue($nodeData['accessRoles'], $connection->getDatabasePlatform());

        $connection->executeQuery(
            'DELETE FROM neos_contentrepository_domain_model_nodedimension'
            . ' WHERE nodedata IN ('
            . '   SELECT persistence_object_identifier FROM neos_contentrepository_domain_model_nodedata'
            . '   WHERE identifier = :identifier'
            . '   AND workspace = :workspace'
            . '   AND dimensionshash = :dimensionsHash'
            . ' )',
            [
                'identifier' => $nodeData['identifier'],
                'workspace' => $nodeData['workspace'],
                'dimensionsHash' => $nodeData['dimensionsHash']
            ]
        );

        /** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->delete()
            ->from(NodeData::class, 'n')
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
        $queryParts = [];
        $queryArguments = [];
        $queryTypes = [];
        foreach ($this->nodeDataPropertyNames as $propertyName => $propertyConfig) {
            if (isset($nodeData[$propertyName])) {
                $queryParts[$propertyName] = ':' . $propertyName;
                $queryArguments[$propertyName] = $nodeData[$propertyName];
                if (isset($propertyConfig['columnType'])) {
                    $queryTypes[$propertyName] = $propertyConfig['columnType'];
                }
            }
        }
        $connection->executeUpdate('INSERT INTO neos_contentrepository_domain_model_nodedata (' . implode(', ', array_keys($queryParts)) . ') VALUES (' . implode(', ', $queryParts) . ')', $queryArguments, $queryTypes);

        foreach ($dimensionValues as $dimension => $values) {
            foreach ($values as $value) {
                $nodeDimension = [
                    'persistence_object_identifier' => Algorithms::generateUUID(),
                    'nodedata' => $nodeData['Persistence_Object_Identifier'],
                    'name' => $dimension,
                    'value' => $value
                ];
                $connection->insert('neos_contentrepository_domain_model_nodedimension', $nodeDimension);
            }
        }
    }
}
