<?php
namespace TYPO3\TYPO3\Command;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
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
class SiteCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

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

}

?>