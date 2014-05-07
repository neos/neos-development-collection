<?php
namespace TYPO3\Neos\Command;

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
use TYPO3\Neos\Domain\Model\Site;

/**
 * The Site Command Controller
 *
 * @Flow\Scope("singleton")
 */
class SiteCommandController extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Service\SiteImportService
	 */
	protected $siteImportService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Service\SiteExportService
	 */
	protected $siteExportService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * Import sites content
	 *
	 * This command allows for importing one or more sites or partial content from an XML source. The format must
	 * be identical to that produced by the export command.
	 *
	 * If a filename is specified, this command expects the corresponding file to contain the XML structure
	 *
	 * If a package key is specified, this command expects a Sites.xml file to be located in the private resources
	 * directory of the given package:
	 * .../Resources/Private/Content/Sites.xml
	 *
	 * @param string $packageKey Package key specifying the package containing the sites content
	 * @param string $filename relative path and filename to the XML file containing the sites content
	 * @return void
	 */
	public function importCommand($packageKey = NULL, $filename = NULL) {
		$contentContext = $this->createContext();

		if ($filename !== NULL) {
			try {
				$this->siteImportService->importSitesFromFile($filename, $contentContext);
			} catch (\Exception $exception) {
				$this->outputLine('Error: During the import of the file "%s" an exception occurred: %s', array($filename, $exception->getMessage()));
				$this->quit(1);
			}
		} elseif ($packageKey !== NULL) {
			try {
				$this->siteImportService->importFromPackage($packageKey, $contentContext);
			} catch (\Exception $exception) {
				$this->outputLine('Error: During the import of the "Sites.xml" from the package "%s" an exception occurred: %s', array($packageKey, $exception->getMessage()));
				$this->quit(1);
			}
		} else {
			$this->outputLine('You have to specify either "--package-key" or "--filename"');
			$this->quit(1);
		}
		$this->outputLine('Import finished.');
	}

	/**
	 * Export sites content
	 *
	 * This command exports all or one specific site with all its content into an XML
	 * format.
	 *
	 * If the filename option is given, any resources will be exported
	 * to files in a folder named "Resources" alongside the XML file.
	 *
	 * If not given, the XML will be printed to standard output and assets will be embedded
	 * into the XML in base64 encoded form.
	 *
	 * @param string $siteNode the node name of the site to be exported; if none given will export all sites
	 * @param boolean $tidy Whether to export formatted XML
	 * @param string $filename relative path and filename to the XML file to create. Any resource will be stored in a sub folder "Resources". If omitted the export will be printed to standard output
	 * @return void
	 */
	public function exportCommand($siteNode = NULL, $tidy = FALSE, $filename = NULL) {
		$contentContext = $this->createContext();

		if ($siteNode === NULL) {
			$sites = $this->siteRepository->findAll()->toArray();
		} else {
			$sites = $this->siteRepository->findByNodeName($siteNode)->toArray();
		}
		if (count($sites) === 0) {
			$this->outputLine('Error: No site for exporting found');
			$this->quit(1);
		}
		if ($filename === NULL) {
			$this->output($this->siteExportService->export($sites, $contentContext, $tidy));
		} else {
			$this->siteExportService->exportToFile($sites, $contentContext, $tidy, $filename);
			if ($siteNode !== NULL) {
				$this->outputLine('The site "%s" has been exported to "%s".', array($siteNode, $filename));
			} else {
				$this->outputLine('All sites have been exported to "%s".', array($filename));
			}
		}
	}

	/**
	 * Remove all content and related data - for now. In the future we need some more sophisticated cleanup.
	 *
	 * @param boolean $confirmation
	 * @param string $siteNodeName Name of a site root node to clear only content of this site.
	 * @return void
	 */
	public function pruneCommand($confirmation = FALSE, $siteNodeName = NULL) {
		if ($confirmation === FALSE) {
			$this->outputLine('Please confirm that you really want to remove all content from the database.');
			$this->outputLine('');
			$this->outputLine('Syntax:');
			$this->outputLine('  ./flow site:prune --confirmation TRUE');
			$this->quit(1);
		}

		if ($siteNodeName !== NULL) {
			$possibleSite = $this->siteRepository->findOneByNodeName($siteNodeName);
			if ($possibleSite === NULL) {
				$this->outputLine('The given site site node did not match an existing site.');
				$this->quit(1);
			}
			$this->pruneSite($possibleSite);
			$this->outputLine('Site with root "' . $siteNodeName . '" has been removed.');
		} else {
			$this->pruneAll();
			$this->outputLine('All sites and content have been removed.');
		}
	}

	/**
	 * Remove given site all nodes for that site and all domains associated.
	 *
	 * @param Site $site
	 * @return void
	 */
	protected function pruneSite(Site $site) {
		$siteNodePath = '/sites/' . $site->getNodeName();
		$this->nodeDataRepository->removeAllInPath($siteNodePath);
		$siteNodes = $this->nodeDataRepository->findByPath($siteNodePath);
		foreach ($siteNodes as $siteNode) {
			$this->nodeDataRepository->remove($siteNode);
		}

		$domainsForSite = $this->domainRepository->findBySite($site);
		foreach ($domainsForSite as $domain) {
			$this->domainRepository->remove($domain);
		}
		$this->siteRepository->remove($site);
	}

	/**
	 * Remove all nodes, workspaces, domains and sites.
	 *
	 * @return void
	 */
	protected function pruneAll() {
		$this->nodeDataRepository->removeAll();
		$this->workspaceRepository->removeAll();
		$this->domainRepository->removeAll();
		$this->siteRepository->removeAll();
	}

	/**
	 * Display a list of available sites
	 *
	 * @return void
	 */
	public function listCommand() {
		$sites = $this->siteRepository->findAll();

		if ($sites->count() === 0) {
			$this->outputLine('No sites available');
			$this->quit(0);
		}

		$longestSiteName = 4;
		$longestNodeName = 9;
		$longestSiteResource = 17;
		$availableSites = array();

		foreach ($sites as $site) {
			/** @var \TYPO3\Neos\Domain\Model\Site $site */
			array_push($availableSites, array(
				'name' => $site->getName(),
				'nodeName' => $site->getNodeName(),
				'siteResourcesPackageKey' => $site->getSiteResourcesPackageKey()
			));
			if (strlen($site->getName()) > $longestSiteName) {
				$longestSiteName = strlen($site->getName());
			}
			if (strlen($site->getNodeName()) > $longestNodeName) {
				$longestNodeName = strlen($site->getNodeName());
			}
			if (strlen($site->getSiteResourcesPackageKey()) > $longestSiteResource) {
				$longestSiteResource = strlen($site->getSiteResourcesPackageKey());
			}
		}

		$this->outputLine();
		$this->outputLine(' ' . str_pad('Name', $longestSiteName + 15) . str_pad('Node name', $longestNodeName + 15) . 'Resources package');
		$this->outputLine(str_repeat('-', $longestSiteName + $longestNodeName + $longestSiteResource + 15 + 15 + 2));
		foreach ($availableSites as $site) {
			$this->outputLine(' ' . str_pad($site['name'], $longestSiteName + 15) . str_pad($site['nodeName'], $longestNodeName + 15) . $site['siteResourcesPackageKey']);
		}
		$this->outputLine();
	}

	/**
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected function createContext() {
		return $this->contextFactory->create(array(
			'workspaceName' => 'live',
			'invisibleContentShown' => TRUE,
			'inaccessibleContentShown' => TRUE
		));
	}
}
