<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Command;

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
class SiteCommandController extends \F3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @inject
	 * @var \F3\TYPO3\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @inject
	 * @var \F3\TYPO3\Domain\Service\SiteImportService
	 */
	protected $siteImportService;

	/**
	 * @inject
	 * @var \F3\TYPO3\Domain\Service\SiteExportService
	 */
	protected $siteExportService;

	/**
	 * Action to import XML content
	 *
	 * @return void
	 */
	public function importCommand() {
		try {
			$this->siteImportService->importSitesFromFile('php://stdin');
			$this->response->setContent('Import finished.');
		} catch (\Exception $exception) {
			$this->response->setContent('Error: During import an exception occured. ' . $exception->getMessage());
		}
	}

	/**
	 * Action to export all sites
	 *
	 * @return void
	 */
	public function exportCommand() {
		$sites = $this->siteRepository->findAll();
		$this->response->setContent($this->siteExportService->export($sites->toArray()));
	}

}

?>