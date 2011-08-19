<?php
namespace TYPO3\TYPO3\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The Site Export Service
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class SiteExportService {

	/**
	 * @inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * Fetches the site with the given name and exports it into XML.
	 *
	 * @param array<\TYPO3\TYPO3\Domain\Model\Site> $sites
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function export(array $sites) {
		$contentContext = new \TYPO3\TYPO3CR\Domain\Service\Context('live');
		$contentContext->showHidden(TRUE);

		$xmlWriter = new \XMLWriter();
		$xmlWriter->openUri('php://output');
		$xmlWriter->startDocument('1.0', 'UTF-8');
		$xmlWriter->startElement('root');

		/** @var $site \TYPO3\TYPO3\Domain\Model\Site */
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
			$node = $contentContext->getNode('/Sites/' . $site->getNodeName());
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
		$xmlWriter->writeAttribute('type', $node->getContentType());
		$xmlWriter->writeAttribute('nodeName', $node->getName());
		$xmlWriter->writeAttribute('locale', '');
		if ($node->isHidden() == TRUE) {
			$xmlWriter->writeAttribute('hidden', 'true');
		}
		if ($node->isHiddenInIndex() == TRUE) {
			$xmlWriter->writeAttribute('hiddenInIndex', 'true');
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
				if (strpos($propertyValue, '<') !== FALSE || strpos($propertyValue, '>') !== FALSE || strpos($propertyValue, '&') !== FALSE) {
					$xmlWriter->startElement($propertyName);
					$xmlWriter->writeCdata($propertyValue);
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
}
?>