<?php
namespace TYPO3\TYPO3\Command;

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
 * The Site Command Controller Service
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope singleton
 */
class SiteCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @inject
	 * @var \TYPO3\TYPO3\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @inject
	 * @var \TYPO3\TYPO3\Domain\Service\SiteImportService
	 */
	protected $siteImportService;

	/**
	 * @inject
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