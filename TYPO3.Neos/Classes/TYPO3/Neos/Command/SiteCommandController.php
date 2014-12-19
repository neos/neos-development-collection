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
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\SiteExportService;
use TYPO3\Neos\Domain\Service\SiteImportService;
use TYPO3\Neos\Domain\Service\SiteService;

/**
 * The Site Command Controller
 *
 * @Flow\Scope("singleton")
 */
class SiteCommandController extends CommandController {

	/**
	 * @Flow\Inject
	 * @var SiteImportService
	 */
	protected $siteImportService;

	/**
	 * @Flow\Inject
	 * @var SiteExportService
	 */
	protected $siteExportService;

	/**
	 * @Flow\Inject
	 * @var SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @Flow\Inject
	 * @var SiteService
	 */
	protected $siteService;

	/**
	 * Import sites content
	 *
	 * This command allows for importing one or more sites or partial content from an XML source. The format must
	 * be identical to that produced by the export command.
	 *
	 * If a filename is specified, this command expects the corresponding file to contain the XML structure. The
	 * filename php://stdin can be used to read from standard input.
	 *
	 * If a package key is specified, this command expects a Sites.xml file to be located in the private resources
	 * directory of the given package (Resources/Private/Content/Sites.xml).
	 *
	 * @param string $packageKey Package key specifying the package containing the sites content
	 * @param string $filename relative path and filename to the XML file containing the sites content
	 * @return void
	 */
	public function importCommand($packageKey = NULL, $filename = NULL) {
		if ($packageKey === NULL && $filename === NULL) {
			$this->outputLine('You have to specify either "--package-key" or "--filename"');
			$this->quit(1);
		}
		$site = NULL;
		if ($filename !== NULL) {
			try {
				$site = $this->siteImportService->importFromFile($filename);
			} catch (\Exception $exception) {
				$this->outputLine('Error: During the import of the file "%s" an exception occurred: %s', array($filename, $exception->getMessage()));
				$this->quit(1);
			}
		} else {
			try {
				$site = $this->siteImportService->importFromPackage($packageKey);
			} catch (\Exception $exception) {
				$this->outputLine('Error: During the import of the "Sites.xml" from the package "%s" an exception occurred: %s', array($packageKey, $exception->getMessage()));
				$this->quit(1);
			}
		}
		$this->outputLine('Import of site "%s" finished.', array($site->getName()));
	}

	/**
	 * Export sites content
	 *
	 * This command exports all or one specific site with all its content into an XML format.
	 *
	 * If the package key option is given, the site(s) will be exported to the given package in the default
	 * location Resources/Private/Content/Sites.xml.
	 *
	 * If the filename option is given, any resources will be exported to files in a folder named "Resources"
	 * alongside the XML file.
	 *
	 * If neither the filename nor the package key option are given, the XML will be printed to standard output and
	 * assets will be embedded into the XML in base64 encoded form.
	 *
	 * @param string $siteNode the node name of the site to be exported; if none given will export all sites
	 * @param boolean $tidy Whether to export formatted XML
	 * @param string $filename relative path and filename to the XML file to create. Any resource will be stored in a sub folder "Resources".
	 * @param string $packageKey Package to store the XML file in. Any resource will be stored in a sub folder "Resources".
	 * @return void
	 */
	public function exportCommand($siteNode = NULL, $tidy = FALSE, $filename = NULL, $packageKey = NULL) {
		if ($siteNode === NULL) {
			$sites = $this->siteRepository->findAll()->toArray();
		} else {
			$sites = $this->siteRepository->findByNodeName($siteNode)->toArray();
		}

		if (count($sites) === 0) {
			$this->outputLine('Error: No site for exporting found');
			$this->quit(1);
		}

		if ($packageKey !== NULL) {
			$this->siteExportService->exportToPackage($sites, $tidy, $packageKey);
			if ($siteNode !== NULL) {
				$this->outputLine('The site "%s" has been exported to package "%s".', array($siteNode, $packageKey));
			} else {
				$this->outputLine('All sites have been exported to package "%s".', array($packageKey));
			}
		} elseif ($filename !== NULL) {
			$this->siteExportService->exportToFile($sites, $tidy, $filename);
			if ($siteNode !== NULL) {
				$this->outputLine('The site "%s" has been exported to "%s".', array($siteNode, $filename));
			} else {
				$this->outputLine('All sites have been exported to "%s".', array($filename));
			}
		} else {
			$this->output($this->siteExportService->export($sites, $tidy));
		}
	}

	/**
	 * Remove all content and related data - for now. In the future we need some more sophisticated cleanup.
	 *
	 * @param string $siteNodeName Name of a site root node to clear only content of this site.
	 * @return void
	 */
	public function pruneCommand($siteNodeName = NULL) {
		if ($siteNodeName !== NULL) {
			$possibleSite = $this->siteRepository->findOneByNodeName($siteNodeName);
			if ($possibleSite === NULL) {
				$this->outputLine('The given site site node did not match an existing site.');
				$this->quit(1);
			}
			$this->siteService->pruneSite($possibleSite);
			$this->outputLine('Site with root "' . $siteNodeName . '" has been removed.');
		} else {
			$this->siteService->pruneAll();
			$this->outputLine('All sites and content have been removed.');
		}
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
}