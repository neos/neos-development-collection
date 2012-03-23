<?php
namespace TYPO3\TYPO3\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The Site Import Service
 *
 * @FLOW3\Scope("prototype")
 * @api
 */
class SiteImportService {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3\Domain\Repository\DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Checks for the presence of Sites.xml in the given package and imports
	 * it if found.
	 *
	 * @param string $packageKey
	 * @return void
	 */
	public function importFromPackage($packageKey) {
		if (!$this->packageManager->isPackageActive($packageKey)) {
			throw new \TYPO3\TYPO3\Exception('Error: Package "' . $packageKey . '" is not active.');
		} elseif (!file_exists('resource://' . $packageKey . '/Private/Content/Sites.xml')) {
			throw new \TYPO3\TYPO3\Exception('Error: No content found in package "' . $packageKey . '".');
		} else {
			try {
				$this->importSitesFromFile('resource://' . $packageKey . '/Private/Content/Sites.xml');
			} catch (\Exception $exception) {
				throw new \TYPO3\TYPO3\Exception('Error: During import an exception occured. ' . $exception->getMessage(), 1300360480, $exception);
			}
		}
	}

	/**
	 * Checks for the presence of Sites.xml in the given package and re-imports
	 * the nodes of the live workspace.
	 *
	 * @param string $packageKey
	 * @return void
	 */
	public function updateFromPackage($packageKey) {
		if (!$this->packageManager->isPackageActive($packageKey)) {
			throw new \TYPO3\TYPO3\Exception('Error: Package "' . $packageKey . '" is not active.');
		} elseif (!file_exists('resource://' . $packageKey . '/Private/Content/Sites.xml')) {
			throw new \TYPO3\TYPO3\Exception('Error: No content found in package "' . $packageKey . '".');
		}

		$contentContext = new \TYPO3\TYPO3\Domain\Service\ContentContext('live');
		$siteNode = $contentContext->getCurrentSiteNode();

		try {
			$this->importSitesFromFile('resource://' . $packageKey . '/Private/Content/Sites.xml');
		} catch (\Exception $exception) {
			throw new \TYPO3\TYPO3\Exception('Error: During import an exception occured. ' . $exception->getMessage(), 1300360479, $exception);
		}
	}

	/**
	 * @param string $pathAndFilename
	 * @return void
	 */
	public function importSitesFromFile($pathAndFilename) {
		$contentContext = new \TYPO3\TYPO3\Domain\Service\ContentContext('live');
		$contentContext->setInvisibleContentShown(TRUE);
		$contentContext->setInaccessibleContentShown(TRUE);

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
				$site = new \TYPO3\TYPO3\Domain\Model\Site((string)$siteXml['nodeName']);
				$this->siteRepository->add($site);
			} else {
				$this->siteRepository->update($site);
			}
			$site->setName((string)$siteXml->properties->name);
			$site->setState((integer)$siteXml->properties->state);

			$siteResourcesPackageKey = (string)$siteXml->properties->siteResourcesPackageKey;
			if ($this->packageManager->isPackageAvailable($siteResourcesPackageKey) === FALSE) {
				throw new \TYPO3\FLOW3\Package\Exception\UnknownPackageException('Package "' . $siteResourcesPackageKey . '" specified in the XML as site resources package does not exist.', 1303891443);
			}
			if ($this->packageManager->isPackageActive($siteResourcesPackageKey) === FALSE) {
				throw new \TYPO3\FLOW3\Package\Exception\InvalidPackageStateException('Package "' . $siteResourcesPackageKey . '" specified in the XML as site resources package is not active.', 1303898135);
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
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $parentNode
	 * @return void
	 */
	protected function parseNodes(\SimpleXMLElement $parentXml, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $parentNode) {
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
			if ($childNodeXml['hiddenBeforeDateTime'] != '') {
				$childNode->setHiddenBeforeDateTime(\DateTime::createFromFormat(\DateTime::W3C, (string)$childNodeXml['hiddenBeforeDateTime']));
			}
			if ($childNodeXml['hiddenAfterDateTime'] != '') {
				$childNode->setHiddenAfterDateTime(\DateTime::createFromFormat(\DateTime::W3C, (string)$childNodeXml['hiddenAfterDateTime']));
			}

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