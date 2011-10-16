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
	 * If a package key is specified, this command expects a Sites.xml file to be located in the private resources
	 * directory of the given package:
	 *
	 * .../Resources/Private/Content/Sites.xml
	 *
	 * Alternatively the XML content may be passed through STDIN in which case the package key argument must be omitted.
	 *
	 * @param string $packageKey Package key specifying the package containing the sites content
	 * @return void
	 */
	public function importCommand($packageKey = NULL) {
		try {
			if ($packageKey !== NULL) {
				$this->siteImportService->importFromPackage($packageKey);
			} else {
				$this->siteImportService->importSitesFromFile('php://stdin');
			}
			$this->outputLine('Import finished.');
		} catch (\Exception $exception) {
			$this->outputLine('Error: During import an exception occured. ' . $exception->getMessage());
		}
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