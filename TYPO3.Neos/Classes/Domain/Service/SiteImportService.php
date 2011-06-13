<?php
namespace F3\TYPO3\Domain\Service;

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
 * The Site Import Service
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @api
 */
class SiteImportService {

	/**
	 * @inject
	 * @var \F3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @inject
	 * @var \F3\TYPO3\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @inject
	 * @var \F3\TYPO3\Domain\Repository\DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Repository\ContentTypeRepository
	 */
	protected $contentTypeRepository;

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

	/**
	 * @inject
	 * @var \F3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Checks for the presence of Content.xml in the given package and imports
	 * it if found.
	 *
	 * @param string $packageKey
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Christian Müller <christian@kitsunet.de>
	 */
	public function importFromPackage($packageKey) {
		if (!$this->packageManager->isPackageActive($packageKey)) {
			throw new \F3\TYPO3\Exception('Error: Package "' . $packageKey . '" is not active.');
		} elseif (!file_exists('resource://' . $packageKey . '/Private/Content/Sites.xml')) {
			throw new \F3\TYPO3\Exception('Error: No content found in package "' . $packageKey . '".');
		} else {

				// Remove all content and related data - for now. In the future we
				// need some more sophisticated cleanup and don't delete everything
				// without asking ...

			$this->nodeRepository->removeAll();
			$this->workspaceRepository->removeAll();
			$this->domainRepository->removeAll();
			$this->siteRepository->removeAll();
			$this->contentTypeRepository->removeAll();

			$this->persistenceManager->persistAll();

			$folderContentType = $this->contentTypeManager->createContentType('TYPO3CR:Folder');

			$pageContentType = $this->contentTypeManager->createContentType('TYPO3:Page');
			$pageContentType->setDeclaredSuperTypes(new \Doctrine\Common\Collections\ArrayCollection(array($folderContentType)));

			try {
				$this->importSitesFromFile('resource://' . $packageKey . '/Private/Content/Sites.xml');
			} catch (\Exception $exception) {
				throw new \F3\TYPO3\Exception('Error: During import an exception occured. ' . $exception->getMessage(), 1300360480, $exception);
			}
		}
	}

	/**
	 * Checks for the presence of Content.xml in the given package and re-imports
	 * the nodes of the live workspace.
	 *
	 * @param string $packageKey
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function updateFromPackage($packageKey) {
		if (!$this->packageManager->isPackageActive($packageKey)) {
			throw new \F3\TYPO3\Exception('Error: Package "' . $packageKey . '" is not active.');
		} elseif (!file_exists('resource://' . $packageKey . '/Private/Content/Sites.xml')) {
			throw new \F3\TYPO3\Exception('Error: No content found in package "' . $packageKey . '".');
		}

		$contentContext = new \F3\TYPO3\Domain\Service\ContentContext('live');
		$siteNode = $contentContext->getCurrentSiteNode();
		if ($siteNode !== NULL) {
			$siteNode->remove();
			$this->persistenceManager->persistAll();
		}

		try {
			$this->importSitesFromFile('resource://' . $packageKey . '/Private/Content/Sites.xml');
		} catch (\Exception $exception) {
			throw new \F3\TYPO3\Exception('Error: During import an exception occured. ' . $exception->getMessage(), 1300360479, $exception);
		}
	}

	/**
	 * @param string $pathAndFilename
	 * @return void
	 */
	public function importSitesFromFile($pathAndFilename) {
		$contentContext = new \F3\TYPO3\Domain\Service\ContentContext('live');

			// no file_get_contents here because it does not work on php://stdin
		$fp = fopen($pathAndFilename, 'rb');
		$xmlString = '';
		while (!feof($fp)) {
			$xmlString .= fread($fp, 4096);
		}
		fclose($fp);

		$xml = new \SimpleXMLElement($xmlString);
		foreach ($xml->site as $siteXml) {
			$site = $this->siteRepository->findOneByNodeName((string)$siteXml['nodeName']);
			if ($site === NULL) {
				$site = new \F3\TYPO3\Domain\Model\Site((string)$siteXml['nodeName']);
				$this->siteRepository->add($site);
			}
			$site->setName((string)$siteXml->properties->name);
			$site->setState((integer)$siteXml->properties->state);

			$siteResourcesPackageKey = (string)$siteXml->properties->siteResourcesPackageKey;
			if ($this->packageManager->isPackageAvailable($siteResourcesPackageKey) === FALSE) {
				throw new \F3\FLOW3\Package\Exception\UnknownPackageException('Package "' . $siteResourcesPackageKey . '" specified in the XML as site resources package does not exist.', 1303891443);
			}
			if ($this->packageManager->isPackageActive($siteResourcesPackageKey) === FALSE) {
				throw new \F3\FLOW3\Package\Exception\InvalidPackageStateException('Package "' . $siteResourcesPackageKey . '" specified in the XML as site resources package is not active.', 1303898135);
			}
			$site->setSiteResourcesPackageKey($siteResourcesPackageKey);

			$rootNode = $contentContext->getWorkspace()->getRootNode();

			if($rootNode->getNode('/sites') === NULL) {
				$rootNode->createNode('sites');
			}

			$siteNode = $rootNode->getNode('/sites/' . $site->getNodeName());
			if ($siteNode === NULL) {
				$siteNode = $rootNode->getNode('/sites')->createNode($site->getNodeName());
			}
			$siteNode->setContentObject($site);

			$this->parseNodes($siteXml, $siteNode);
		}
	}

	/**
	 * Iterates over the nodes and adds them to the workspace.
	 *
	 * @param \SimpleXMLElement $parentXml
	 * @param \F3\TYPO3CR\Domain\Model\NodeInterface $parentNode
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseNodes(\SimpleXMLElement $parentXml, \F3\TYPO3CR\Domain\Model\NodeInterface $parentNode) {
		foreach ($parentXml->node as $childNodeXml) {
			$childNode = $parentNode->getNode((string)$childNodeXml['nodeName']);
			if ($childNode === NULL) {
				$identifier = (string)$childNodeXml['identifier'] === '' ? NULL : (string)$childNodeXml['identifier'];
				$childNode = $parentNode->createNode((string)$childNodeXml['nodeName'], NULL, $identifier);
			}

			$contentTypeName = (string)$childNodeXml['type'];
			if (!$this->contentTypeManager->hasContentType($contentTypeName)) {
				$this->contentTypeManager->createContentType($contentTypeName);
			}
			$childNode->setContentType($contentTypeName);

			$childNode->setHidden((boolean)$childNodeXml['hidden']);
			$childNode->setHiddenInIndex((boolean)$childNodeXml['hiddenInIndex']);

			if ($childNodeXml->properties) {
				foreach ($childNodeXml->properties->children() as $childXml) {
					$childNode->setProperty($childXml->getName(), (string)$childXml);
				}
			}

			if ($childNodeXml->accessRoles) {
				$accessRoles = array();
				foreach ($childNodeXml->accessRoles->children() as $childXml) {
					$accessRoles[] = (string)$childXml;
				}
				$childNode->setAccessRoles($accessRoles);
			}

			if ($childNodeXml->node) {
				$this->parseNodes($childNodeXml, $childNode);
			}
		}
	}
}
?>