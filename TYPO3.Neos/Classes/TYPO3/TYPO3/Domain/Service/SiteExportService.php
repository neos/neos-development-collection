<?php
namespace TYPO3\TYPO3\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * The Site Export Service
 *
 * @Flow\Scope("prototype")
 */
class SiteExportService {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * Fetches the site with the given name and exports it into XML.
	 *
	 * @param array<\TYPO3\TYPO3\Domain\Model\Site> $sites
	 * @return void
	 */
	public function export(array $sites) {
		$this->nodeRepository->getContext()->setInvisibleContentShown(TRUE);
		$this->nodeRepository->getContext()->setInaccessibleContentShown(TRUE);

		$xmlWriter = new \XMLWriter();
		$xmlWriter->openUri('php://output');
		$xmlWriter->startDocument('1.0', 'UTF-8');
		$xmlWriter->startElement('root');

		foreach ($sites as $site) {
			$xmlWriter->startElement('site');

				// site attributes
			$xmlWriter->writeAttribute('nodeName', $site->getNodeName());

				// site properties
			$xmlWriter->startElement('properties');
			$xmlWriter->writeElement('name', $site->getName());
			$xmlWriter->writeElement('state', $site->getState());
			$xmlWriter->writeElement('siteResourcesPackageKey', $site->getSiteResourcesPackageKey());
			$xmlWriter->endElement();

				// on to the nodes...
			$node = $this->nodeRepository->getContext()->getNode('/Sites/' . $site->getNodeName());
			foreach ($node->getChildNodes() as $childNode) {
				$this->exportNode($childNode, $xmlWriter);
			}

			$xmlWriter->endElement();
		}
		$xmlWriter->endElement();
		$xmlWriter->endDocument();

		$xmlWriter->flush();
	}

	/**
	 * Export a single node.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Node $node
	 * @param \XMLWriter $xmlWriter
	 * @return void
	 */
	protected function exportNode(\TYPO3\TYPO3CR\Domain\Model\Node $node, \XMLWriter $xmlWriter) {
		$xmlWriter->startElement('node');

			// node attributes
		$xmlWriter->writeAttribute('identifier', $node->getIdentifier());
		$xmlWriter->writeAttribute('type', $node->getContentType()->getName());
		$xmlWriter->writeAttribute('nodeName', $node->getName());
		$xmlWriter->writeAttribute('locale', '');
		if ($node->isHidden() === TRUE) {
			$xmlWriter->writeAttribute('hidden', 'true');
		}
		if ($node->isHiddenInIndex() === TRUE) {
			$xmlWriter->writeAttribute('hiddenInIndex', 'true');
		}
		$hiddenBeforeDateTime = $node->getHiddenBeforeDateTime();
		if ($hiddenBeforeDateTime !== NULL) {
			$xmlWriter->writeAttribute('hiddenBeforeDateTime', $hiddenBeforeDateTime->format(\DateTime::W3C));
		}
		$hiddenAfterDateTime = $node->getHiddenAfterDateTime();
		if ($hiddenAfterDateTime !== NULL) {
			$xmlWriter->writeAttribute('hiddenAfterDateTime', $hiddenAfterDateTime->format(\DateTime::W3C));
		}

			// access roles
		$accessRoles = $node->getAccessRoles();
		if (count($accessRoles) > 0) {
			$xmlWriter->startElement('accessRoles');
			foreach ($accessRoles as $role) {
				$xmlWriter->writeElement('role', $role);
			}
			$xmlWriter->endElement();
		}

			// node properties
		$properties = $node->getProperties();
		if (count($properties) > 0) {
			$xmlWriter->startElement('properties');
			foreach ($properties as $propertyName => $propertyValue) {
				if (is_object($propertyValue)) {
					$xmlWriter->startElement($propertyName);
					$xmlWriter->writeAttribute('__type', 'object');
					$xmlWriter->writeAttribute('__classname', get_class($propertyValue));
					$this->objectToXml($propertyValue, $xmlWriter);
					$xmlWriter->endElement();
				} elseif (strpos($propertyValue, '<') !== FALSE || strpos($propertyValue, '>') !== FALSE || strpos($propertyValue, '&') !== FALSE) {
					$xmlWriter->startElement($propertyName);
					if (strpos($propertyValue, '<![CDATA[') !== FALSE) {
						$xmlWriter->writeCdata(str_replace(']]>', ']]]]><![CDATA[>', $propertyValue));
					} else {
						$xmlWriter->writeCdata($propertyValue);
					}
					$xmlWriter->endElement();
				} else {
					$xmlWriter->writeElement($propertyName, $propertyValue);
				}
			}
			$xmlWriter->endElement();
		}

			// and the child nodes recursively
		foreach ($node->getChildNodes() as $childNode) {
			$this->exportNode($childNode, $xmlWriter);
		}

		$xmlWriter->endElement();
	}

	/**
	 * Handles conversion of objects into a string format that can be exported in our
	 * XML format.
	 *
	 * Note: currently only ImageVariant instances are supported.
	 *
	 * @param $object
	 * @param \XMLWriter $xmlWriter
	 * @return void
	 */
	protected function objectToXml($object, \XMLWriter $xmlWriter) {
		$className = get_class($object);
		switch ($className) {
			case 'TYPO3\Media\Domain\Model\ImageVariant':
				$xmlWriter->startElement('processingInstructions');
				$xmlWriter->writeCdata(serialize($object->getProcessingInstructions()));
				$xmlWriter->endElement();

				$xmlWriter->startElement('originalImage');
				$xmlWriter->writeAttribute('__type', 'object');
				$xmlWriter->writeAttribute('__classname', '\TYPO3\Media\Domain\Model\Image');

				$xmlWriter->startElement('resource');
				$xmlWriter->writeAttribute('__type', 'object');
				$xmlWriter->writeAttribute('__classname', '\TYPO3\Flow\Resource\Resource');
				$resource = $object->getOriginalImage()->getResource();
				$xmlWriter->writeElement('filename', $resource->getFilename());
				$xmlWriter->writeElement('content', base64_encode(file_get_contents($resource->getUri())));
				$xmlWriter->endElement();

				$xmlWriter->endElement();
			break;
			default:
				throw new \TYPO3\TYPO3\Domain\Exception('Unsupported object of type "' . get_class($className) . '" hit during XML export.', 1347144928);
		}
	}
}
?>