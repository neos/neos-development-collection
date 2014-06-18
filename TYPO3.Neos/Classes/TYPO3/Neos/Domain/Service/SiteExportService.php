<?php
namespace TYPO3\Neos\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Files;
use TYPO3\Media\Domain\Model\ImageVariant;
use TYPO3\Neos\Domain\Exception as DomainException;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

/**
 * The Site Export Service
 *
 * @Flow\Scope("singleton")
 */
class SiteExportService {

	/**
	 * @Flow\Inject
	 * @var ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * Absolute path to exported resources, or NULL if resources should be inlined in the exported XML
	 *
	 * @var string
	 */
	protected $resourcesPath = NULL;

	/**
	 * The XMLWriter that is used to construct the export.
	 *
	 * @var \XMLWriter
	 */
	protected $xmlWriter;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Fetches the site with the given name and exports it into XML.
	 *
	 * @param array<Site> $sites
	 * @param ContentContext $contentContext
	 * @param boolean $tidy Whether to export formatted XML
	 * @return string
	 */
	public function export(array $sites, ContentContext $contentContext, $tidy = FALSE) {
		$this->xmlWriter = new \XMLWriter();
		$this->xmlWriter->openMemory();

		if ($tidy) {
			$this->xmlWriter->setIndent(TRUE);
		}
		$this->exportSites($sites, $contentContext);

		return $this->xmlWriter->outputMemory(TRUE);
	}

	/**
	 * Fetches the site with the given name and exports it into XML.
	 *
	 * @param array<Site> $sites
	 * @param ContentContext $contentContext
	 * @param boolean $tidy Whether to export formatted XML
	 * @param string $pathAndFilename Path to where the export output should be saved to
	 * @return void
	 */
	public function exportToFile(array $sites, ContentContext $contentContext, $tidy = FALSE, $pathAndFilename) {
		$this->resourcesPath = Files::concatenatePaths(array(dirname($pathAndFilename), 'Resources'));
		Files::createDirectoryRecursively($this->resourcesPath);

		$this->xmlWriter = new \XMLWriter();
		$this->xmlWriter->openUri($pathAndFilename);

		if ($tidy) {
			$this->xmlWriter->setIndent(TRUE);
		}
		$this->exportSites($sites, $contentContext);
		$this->xmlWriter->flush();
	}

	/**
	 * Exports the given sites to the XMLWriter
	 *
	 * @param array<Site> $sites
	 * @param ContentContext $contentContext
	 * @return void
	 */
	protected function exportSites(array $sites, ContentContext $contentContext) {
		$this->xmlWriter->startDocument('1.0', 'UTF-8');
		$this->xmlWriter->startElement('root');

		foreach ($sites as $site) {
			$this->exportSite($site, $contentContext);
		}
		$this->xmlWriter->endElement();
		$this->xmlWriter->endDocument();
	}

	/**
	 * Export the given $site to the XMLWriter
	 *
	 * @param Site $site
	 * @param ContentContext $contentContext
	 * @return void
	 */
	protected function exportSite(Site $site, ContentContext $contentContext) {
		$contextProperties = $contentContext->getProperties();
		$contextProperties['currentSite'] = $site;
		/** @var \TYPO3\Neos\Domain\Service\ContentContext $contentContext */
		$contentContext = $this->contextFactory->create($contextProperties);

		$siteNode = $contentContext->getCurrentSiteNode();
		$this->xmlWriter->startElement('site');

		$this->exportNodeAttributes($siteNode);
		$this->exportNodeAccessRoles($siteNode);
		$siteProperties = array(
			'name' => $site->getName(),
			'state' => $site->getState(),
			'siteResourcesPackageKey' => $site->getSiteResourcesPackageKey()
		);
		if ($siteNode->getContentObject() !== NULL) {
			$this->xmlWriter->startElement('properties');
			foreach ($siteProperties as $propertyName => $propertyValue) {
				$this->exportNodeProperty($siteNode, $propertyName, $propertyValue);
			}
			$this->xmlWriter->endElement();
		} else {
			$this->exportNodeProperties($siteNode, $siteProperties);
		}

		foreach ($siteNode->getChildNodes() as $childNode) {
			$this->exportNode($childNode);
		}

		$this->xmlWriter->endElement();
	}

	/**
	 * Export a single node to the XMLWriter
	 *
	 * @param NodeInterface $node
	 * @return void
	 */
	protected function exportNode(NodeInterface $node) {
		$this->xmlWriter->startElement('node');

		$this->exportNodeAttributes($node);
		$this->exportNodeAccessRoles($node);
		$this->exportNodeProperties($node);

		// and the child nodes recursively
		foreach ($node->getChildNodes() as $childNode) {
			$this->exportNode($childNode);
		}

		$this->xmlWriter->endElement();
	}

	/**
	 * Export all attributes of $node to the XMLWriter
	 *
	 * @param NodeInterface $node
	 * @return void
	 */
	protected function exportNodeAttributes(NodeInterface $node) {
		$this->xmlWriter->writeAttribute('identifier', $node->getIdentifier());
		$this->xmlWriter->writeAttribute('type', $node->getNodeType()->getName());
		$this->xmlWriter->writeAttribute('nodeName', $node->getName());
		if ($node->isHidden() === TRUE) {
			$this->xmlWriter->writeAttribute('hidden', 'true');
		}
		if ($node->isHiddenInIndex() === TRUE) {
			$this->xmlWriter->writeAttribute('hiddenInIndex', 'true');
		}
		$hiddenBeforeDateTime = $node->getHiddenBeforeDateTime();
		if ($hiddenBeforeDateTime !== NULL) {
			$this->xmlWriter->writeAttribute('hiddenBeforeDateTime', $hiddenBeforeDateTime->format(\DateTime::W3C));
		}
		$hiddenAfterDateTime = $node->getHiddenAfterDateTime();
		if ($hiddenAfterDateTime !== NULL) {
			$this->xmlWriter->writeAttribute('hiddenAfterDateTime', $hiddenAfterDateTime->format(\DateTime::W3C));
		}
	}

	/**
	 * Export access roles of $node to the XMLWriter
	 *
	 * @param NodeInterface $node
	 * @return void
	 */
	protected function exportNodeAccessRoles(NodeInterface $node) {
		$accessRoles = $node->getAccessRoles();
		if (count($accessRoles) > 0) {
			$this->xmlWriter->startElement('accessRoles');
			foreach ($accessRoles as $role) {
				$this->xmlWriter->writeElement('role', $role);
			}
			$this->xmlWriter->endElement();
		}
	}

	/**
	 * Export all properties of $node to the XMLWriter
	 *
	 * @param NodeInterface $node
	 * @param array $additionalProperties additional key/value pairs to export as properties
	 * @return void
	 */
	protected function exportNodeProperties(NodeInterface $node, array $additionalProperties = array()) {
		$properties = $node->getProperties(TRUE);
		$properties = array_merge($properties, $additionalProperties);
		if (count($properties) > 0) {
			$this->xmlWriter->startElement('properties');
			foreach ($properties as $propertyName => $propertyValue) {
				$this->exportNodeProperty($node, $propertyName, $propertyValue);
			}
			$this->xmlWriter->endElement();
		}
	}

	/**
	 * Exports the property $propertyName to the the XMLWriter
	 *
	 * @param NodeInterface $node
	 * @param string $propertyName
	 * @param mixed $propertyValue
	 * @return void
	 */
	protected function exportNodeProperty(NodeInterface $node, $propertyName, $propertyValue) {
		$nodeType = $node->getNodeType();
		$propertyType = $nodeType->getPropertyType($propertyName);
		switch ($propertyType) {
			case 'boolean':
				$this->xmlWriter->startElement($propertyName);
				$this->xmlWriter->writeAttribute('__type', 'boolean');
				$this->xmlWriter->writeRaw($propertyValue ? 1 : 0);
				$this->xmlWriter->endElement();
				break;
			case 'reference':
				$this->xmlWriter->startElement($propertyName);
				$this->xmlWriter->writeAttribute('__type', 'reference');
				if (!empty($propertyValue)) {
					$this->xmlWriter->startElement('node');
					$this->xmlWriter->writeAttribute('identifier', is_string($propertyValue) ? $propertyValue : '');
					$this->xmlWriter->endElement();
				}
				$this->xmlWriter->endElement();
				break;
			case 'references':
				$this->xmlWriter->startElement($propertyName);
				$this->xmlWriter->writeAttribute('__type', 'references');
				if (is_array($propertyValue)) {
					foreach ($propertyValue as $referencedTargetNode) {
						$this->xmlWriter->startElement('node');
						$this->xmlWriter->writeAttribute('identifier', is_string($referencedTargetNode) ? $referencedTargetNode : '');
						$this->xmlWriter->endElement();
					}
				}
				$this->xmlWriter->endElement();
				break;
			default:
				if (is_object($propertyValue)) {
					$this->xmlWriter->startElement($propertyName);
					$this->xmlWriter->writeAttribute('__type', 'object');
					$this->xmlWriter->writeAttribute('__classname', get_class($propertyValue));
					$objectIdentifier = $this->persistenceManager->getIdentifierByObject($propertyValue);
					if ($objectIdentifier !== NULL) {
						$this->xmlWriter->writeAttribute('__identifier', $objectIdentifier);
					}
					$this->objectToXml($propertyValue);
					$this->xmlWriter->endElement();
				} elseif (is_string($propertyValue) && (strpos($propertyValue, '<') !== FALSE || strpos($propertyValue, '>') !== FALSE || strpos($propertyValue, '&') !== FALSE)) {
					$this->xmlWriter->startElement($propertyName);
					if (strpos($propertyValue, '<![CDATA[') !== FALSE) {
						$this->xmlWriter->writeCdata(str_replace(']]>', ']]]]><![CDATA[>', $propertyValue));
					} else {
						$this->xmlWriter->writeCdata($propertyValue);
					}
					$this->xmlWriter->endElement();
				} else {
					$this->xmlWriter->writeElement($propertyName, is_scalar($propertyValue) ? (string)$propertyValue : '');
				}
				break;
		}
	}

	/**
	 * Handles conversion of objects into a string format that can be exported in our
	 * XML format.
	 * Note: currently only ImageVariant instances are supported.
	 *
	 * @param object $object
	 * @return void
	 * @throws DomainException
	 */
	protected function objectToXml($object) {
		if ($object instanceof ImageVariant) {
			$this->exportImageVariant($object);

			return;
		}
		if ($object instanceof \DateTime) {
			$this->xmlWriter->writeElement('dateTime', $object->format(\DateTime::W3C));

			return;
		}
		throw new DomainException(sprintf('Unsupported object of type "%s" hit during XML export.', get_class($object)), 1347144928);
	}

	/**
	 * Exports the given $imageVariant to the XMLWriter
	 *
	 * @param ImageVariant $imageVariant
	 * @return void
	 */
	protected function exportImageVariant(ImageVariant $imageVariant) {
		$this->xmlWriter->startElement('processingInstructions');
		$this->xmlWriter->writeCdata(serialize($imageVariant->getProcessingInstructions()));
		$this->xmlWriter->endElement();

		$this->xmlWriter->startElement('originalImage');
		$this->xmlWriter->writeAttribute('__type', 'object');
		$this->xmlWriter->writeAttribute('__classname', 'TYPO3\Media\Domain\Model\Image');
		$objectIdentifier = $this->persistenceManager->getIdentifierByObject($imageVariant->getOriginalImage());
		if ($objectIdentifier !== NULL) {
			$this->xmlWriter->writeAttribute('__identifier', $objectIdentifier);
		}

		$this->xmlWriter->startElement('resource');
		$this->xmlWriter->writeAttribute('__type', 'object');
		$this->xmlWriter->writeAttribute('__classname', 'TYPO3\Flow\Resource\Resource');
		$resource = $imageVariant->getOriginalImage()->getResource();
		$objectIdentifier = $this->persistenceManager->getIdentifierByObject($resource);
		if ($objectIdentifier !== NULL) {
			$this->xmlWriter->writeAttribute('__identifier', $objectIdentifier);
		}
		$this->xmlWriter->writeElement('filename', $resource->getFilename());
		if ($this->resourcesPath === NULL) {
			$this->xmlWriter->writeElement('content', base64_encode(file_get_contents($resource->getUri())));
		} else {
			$hash = $resource->getResourcePointer()->getHash();
			copy($resource->getUri(), Files::concatenatePaths(array($this->resourcesPath, $hash)));
			$this->xmlWriter->writeElement('hash', $hash);
		}
		$this->xmlWriter->endElement();
		$this->xmlWriter->endElement();
	}
}
