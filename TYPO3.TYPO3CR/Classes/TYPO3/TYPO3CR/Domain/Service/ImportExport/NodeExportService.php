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
use TYPO3\TYPO3CR\Exception\ExportException;

/**
 * Service for exporting content repository nodes as an XML structure
 *
 * Internally, uses associative arrays instead of Domain Models for performance reasons, so "nodeData" in this
 * class is always an associative array.
 *
 * @Flow\Scope("singleton")
 */
class NodeExportService {

	/**
	 * @var string
	 */
	const SUPPORTED_FORMAT_VERSION = '2.0';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

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
	 * @var \XMLWriter
	 */
	protected $xmlWriter;

	/**
	 * @var array<\Exception> a list of exceptions which happened during export
	 */
	protected $exceptionsDuringExport;

	/**
	 * Exports the node data of all nodes in the given sub-tree
	 * by writing them to the given XMLWriter.
	 *
	 * @param string $startingPointNodePath path to the root node of the sub-tree to export. The specified node will not be included, only its sub nodes.
	 * @param string $workspaceName
	 * @param \XMLWriter $xmlWriter
	 * @param boolean $tidy
	 * @param boolean $endDocument
	 * @param string $resourceSavePath
	 * @return \XMLWriter
	 */
	public function export($startingPointNodePath = '/', $workspaceName = 'live', \XMLWriter $xmlWriter = NULL, $tidy = TRUE, $endDocument = TRUE, $resourceSavePath = NULL) {
		$this->propertyMappingConfiguration = new ImportExportPropertyMappingConfiguration($resourceSavePath);
		$this->exceptionsDuringExport = array();

		$this->xmlWriter = $xmlWriter;
		if ($this->xmlWriter === NULL) {
			$this->xmlWriter = new \XMLWriter();
			$this->xmlWriter->openMemory();
			$this->xmlWriter->setIndent($tidy);
			$this->xmlWriter->startDocument('1.0', 'UTF-8');
		}

		$nodeDataList = $this->findNodeDataListToExport($startingPointNodePath, $workspaceName);
		$this->exportNodeDataList($nodeDataList);

		if ($endDocument) {
			$this->xmlWriter->endDocument();
		}

		$this->handleExceptionsDuringExport();

		return $this->xmlWriter;
	}

	/**
	 * Find all nodes of the specified workspace lying below the path specified by
	 * (and including) the given starting point.
	 *
	 * @param string $pathStartingPoint Absolute path specifying the starting point
	 * @param string $workspace The containing workspace
	 * @return array an array of node-data in array format.
	 */
	protected function findNodeDataListToExport($pathStartingPoint, $workspace = 'live') {
		/** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
		$queryBuilder = $this->entityManager->createQueryBuilder();
		$queryBuilder->select(
			'n.path AS path,'
			. ' n.identifier AS identifier,'
			. ' n.index AS sortingIndex,'
			. ' n.properties AS properties, '
			. ' n.nodeType as nodeType,'
			. ' n.removed AS removed,'
			. ' n.hidden,'
			. ' n.hiddenBeforeDateTime AS hiddenBeforeDateTime,'
			. ' n.hiddenAfterDateTime AS hiddenAfterDateTime,'
			. ' n.hiddenInIndex AS hiddenInIndex,'
			. ' n.accessRoles AS accessRoles,'
			. ' n.version AS version,'
			. ' n.parentPath AS parentPath,'
			. ' n.pathHash AS pathHash,'
			. ' n.dimensionsHash AS dimensionsHash,'
			. ' n.parentPathHash AS parentPathHash,'
			. ' n.dimensionValues AS dimensionValues,'
			. ' w.name AS workspace'
		)->distinct()
			->from('TYPO3\TYPO3CR\Domain\Model\NodeData', 'n')
			->innerJoin('n.workspace', 'w', 'WITH', 'n.workspace=w.name')
			->where('n.workspace = :workspace')
			->setParameter('workspace', $workspace)
			->andWhere('n.path like :childpath')
			->setParameter('childpath', ($pathStartingPoint === '/' ? '%' : $pathStartingPoint . '%'))
			->orderBy('n.identifier', 'ASC')
			->orderBy('n.path', 'ASC');

		$nodeDataList = $queryBuilder->getQuery()->getResult();
		// Sort nodeDataList by path, replacing "/" with "!" (the first visible ASCII character)
		// because there may be characters like "-" in the node path
		// that would break the sorting order
		usort($nodeDataList,
			function ($node1, $node2) {
				return strcmp(
					str_replace("/", "!", $node1['path']),
					str_replace("/", "!", $node2['path'])
				);
			}
		);
		return $nodeDataList;
	}

	/**
	 * Exports the given Nodes into the XML structure, contained in <nodes> </nodes> tags.
	 *
	 * @param array $nodeDataList The nodes to export
	 * @return void The result is written directly into $this->xmlWriter
	 */
	protected function exportNodeDataList(array &$nodeDataList) {
		$this->xmlWriter->startElement('nodes');
		$this->xmlWriter->writeAttribute('formatVersion', self::SUPPORTED_FORMAT_VERSION);

		$nodesStack = array();
		foreach ($nodeDataList as $nodeData) {
			$this->exportNodeData($nodeData, $nodesStack);
		}

		// Close remaining <node> tags according to the stack:
		while (array_pop($nodesStack)) {
			$this->xmlWriter->endElement();
		}

		$this->xmlWriter->endElement();
	}

	/**
	 * Exports a single Node into the XML structure
	 *
	 * @param array $nodeData The node data as an array
	 * @param array $nodesStack The stack keeping track of open tags, as passed by exportNodeDataList()
	 * @return void The result is written directly into $this->xmlWriter
	 */
	protected function exportNodeData(array &$nodeData, array &$nodesStack) {
		if ($nodeData['parentPath'] === '/') {
			$nodeName = substr($nodeData['path'], 1);
		} else {
			$nodeName = substr($nodeData['path'], strlen($nodeData['parentPath']) + 1);
		}

		// is this a variant of currently open node?
		// then close all open nodes until parent is currently open and start new node element
		// else reuse the currently open node element and add a new variant element
		// @todo what about nodes with a different path in some dimension
		$parentNode = end($nodesStack);
		if (!$parentNode || $parentNode['path'] !== $nodeData['path'] || $parentNode['identifier'] !== $nodeData['identifier']) {
			while ($parentNode && $nodeData['parentPath'] !== $parentNode['path']) {
				$this->xmlWriter->endElement();
				array_pop($nodesStack);
				$parentNode = end($nodesStack);
			}

			$nodesStack[] = $nodeData;
			$this->xmlWriter->startElement('node');
			$this->xmlWriter->writeAttribute('identifier', $nodeData['identifier']);
			$this->xmlWriter->writeAttribute('nodeName', $nodeName);
		}

		$this->xmlWriter->startElement('variant');

		if ($nodeData['sortingIndex'] !== NULL) {
			// the "/" node has no sorting index by default; so we should only write it if it has been set.
			$this->xmlWriter->writeAttribute('sortingIndex', $nodeData['sortingIndex']);
		}

		foreach(
			array(
				'workspace',
				'nodeType',
				'version',
				'removed',
				'hidden',
				'hiddenInIndex'
			) as $propertyName) {
			$this->xmlWriter->writeAttribute($propertyName, $nodeData[$propertyName]);
		}

		$this->xmlWriter->startElement('dimensions');
		foreach ($nodeData['dimensionValues'] as $dimensionKey => $dimensionValues) {
			foreach ($dimensionValues as $dimensionValue) {
				$this->xmlWriter->writeElement($dimensionKey, $dimensionValue);
			}
		}
		$this->xmlWriter->endElement();

		foreach(
			array(
				'accessRoles',
				'hiddenBeforeDateTime',
				'hiddenAfterDateTime',
				'contentObjectProxy'
			) as $propertyName) {
			$this->writeConvertedElement($nodeData, $propertyName);
		}

		$this->xmlWriter->startElement('properties');
		foreach ($nodeData['properties'] as $propertyName => $propertyValue) {
			$this->writeConvertedElement($nodeData['properties'], $propertyName);
		}
		$this->xmlWriter->endElement(); // "properties"

		$this->xmlWriter->endElement(); // "variant"
	}

	/**
	 * Writes out a single property into the XML structure.
	 *
	 * @param array $data The data as an array, the given property name is looked up there
	 * @param string $propertyName The name of the property
	 * @param string $elementName an optional name to use, defaults to $propertyName
	 * @return void
	 */
	protected function writeConvertedElement(array &$data, $propertyName, $elementName = NULL) {
		if (array_key_exists($propertyName, $data) && $data[$propertyName] !== NULL) {
			$this->xmlWriter->startElement($elementName ?: $propertyName);

			$this->xmlWriter->writeAttribute('__type', gettype($data[$propertyName]));
			try {
				if (is_object($data[$propertyName]) && !$data[$propertyName] instanceof \DateTime) {
					$objectIdentifier = $this->persistenceManager->getIdentifierByObject($data[$propertyName]);
					if ($objectIdentifier !== NULL) {
						$this->xmlWriter->writeAttribute('__identifier', $objectIdentifier);
					}
					if ($data[$propertyName] instanceof \Doctrine\ORM\Proxy\Proxy) {
						$className = get_parent_class($data[$propertyName]);
					} else {
						$className = get_class($data[$propertyName]);
					}
					$this->xmlWriter->writeAttribute('__classname', $className);
					$this->xmlWriter->writeAttribute('__encoding', 'json');

					$converted = json_encode($this->propertyMapper->convert($data[$propertyName], 'array', $this->propertyMappingConfiguration));
					$this->xmlWriter->text($converted);
				} elseif (is_array($data[$propertyName])) {
					foreach ($data[$propertyName] as $key => $element) {
						$this->writeConvertedElement($data[$propertyName], $key, 'entry' . $key);
					}
				} else {
					if (is_object($data[$propertyName]) && $data[$propertyName] instanceof \DateTime) {
						$this->xmlWriter->writeAttribute('__classname', 'DateTime');
					}
					$this->xmlWriter->text($this->propertyMapper->convert($data[$propertyName], 'string', $this->propertyMappingConfiguration));
				}
			} catch (\Exception $exception) {
				$this->xmlWriter->writeComment(sprintf('Could not convert property "%s" to string.', $propertyName));
				$this->xmlWriter->writeComment($exception->getMessage());
				$this->systemLogger->logException($exception);
				$this->exceptionsDuringExport[] = $exception;
			}

			$this->xmlWriter->endElement();
		}
	}

	/**
	 * If $this->exceptionsDuringImport is non-empty, build up a new composite exception which contains the individual messages and
	 * re-throw that one.
	 */
	protected function handleExceptionsDuringExport() {
		if (count($this->exceptionsDuringExport) > 0) {
			$exceptionMessages = '';
			foreach ($this->exceptionsDuringExport as $i => $exception) {
				$exceptionMessages .= "\n" . $i . ': ' . get_class($exception) . "\n" . $exception->getMessage() . "\n";
			}

			throw new ExportException(sprintf('%s exceptions occured during export. Please see the log for the full exceptions (including stack traces). The exception messages follow below: %s', count($this->exceptionsDuringExport), $exceptionMessages), 1409057360);
		}
	}
}
