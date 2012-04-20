<?php
namespace TYPO3\TYPO3\Command;

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
 * The Site Command Controller Service
 *
 * @FLOW3\Scope("singleton")
 */
class SiteCommandController extends \TYPO3\FLOW3\Cli\CommandController {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3\Domain\Service\SiteImportService
	 */
	protected $siteImportService;

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
	 * @var \TYPO3\TYPO3\Domain\Service\SiteExportService
	 */
	protected $siteExportService;

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
		if ($filename !== NULL) {
			try {
				$this->siteImportService->importSitesFromFile($filename);
			} catch (\Exception $exception) {
				$this->outputLine('Error: During the import of the file "%s" an exception occurred: %s', array($filename, $exception->getMessage()));
				$this->quit(1);
			}
		} else if ($packageKey !== NULL) {
			try {
				$this->siteImportService->importFromPackage($packageKey);
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
	 * Export all sites and their content into an XML format.
	 *
	 * @return void
	 */
	public function exportCommand() {
		$sites = $this->siteRepository->findAll();
		$this->response->setContent($this->siteExportService->export($sites->toArray()));
	}

	/**
	 * Remove all content and related data - for now. In the future we need some more sophisticated cleanup.
	 *
	 * @param boolean $confirmation
	 * @return void
	 */
	public function pruneCommand($confirmation = FALSE) {
		if ($confirmation === FALSE) {
			$this->outputLine('Please confirm that you really want to remove all sites and content from the database.');
			$this->outputLine('');
			$this->outputLine('Syntax:');
			$this->outputLine('  ./flow3 site:prune --confirmation TRUE');
			$this->quit(1);
		}

		$this->nodeRepository->removeAll();
		$this->workspaceRepository->removeAll();
		$this->domainRepository->removeAll();
		$this->siteRepository->removeAll();

		$this->outputLine('All sites and content have been removed.');
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

}
?>