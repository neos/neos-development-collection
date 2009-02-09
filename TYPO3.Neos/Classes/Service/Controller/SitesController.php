<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Service\Controller;

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
 * @package TYPO3
 * @subpackage Service
 * @version $Id$
 */

/**
 * The "Sites" service
 *
 * @package TYPO3
 * @subpackage Service
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SitesController extends \F3\FLOW3\MVC\Controller\RESTController {

	/**
	 * @var \F3\TYPO3\Domain\Model\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * Injects the site repository
	 *
	 * @param \F3\TYPO3\Domain\Model\SiteRepository $siteRepository A reference to the site repository
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSiteRepository(\F3\TYPO3\Domain\Model\SiteRepository $siteRepository) {
		$this->siteRepository = $siteRepository;
	}

	/**
	 * Lists available sites from the repository
	 *
	 * @return string Output of the list view
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function listAction() {
		$sites = $this->siteRepository->findAll();
		$this->view->sites = $sites;
		return $this->view->render();
	}

	/**
	 * Shows properties of a specific site
	 *
	 * @return string Output of the show view
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function showAction() {
		$site = $this->siteRepository->findById($this->arguments['id']);
		if ($site === NULL) $this->throwStatus(404);
		$this->view->site = $site;
		return $this->view->render();
	}

	/**
	 * Creates a new site
	 *
	 * @param string $name Name of the website
	 * @return string The status message
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createAction($name) {
		$site = $this->objectFactory->create('F3\TYPO3\Domain\Model\Site');
		$site->setName($name);
		$this->siteRepository->add($site);

		$this->response->setStatus(201);
		$this->response->setHeader('Location', 'http://t3v5/index_dev.php/typo3/service/v1/sites/' . $site->getId() . '.json');
	}

	/**
	 * Updates an existing site
	 *
	 * @param string $name Name of the website
	 * @return string The status message
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function updateAction($name = NULL) {
		$site = $this->siteRepository->findById($this->arguments['id']);
		if ($site === NULL) $this->throwStatus(404, NULL, 'Unknown Site');
		if ($name !== NULL) $site->setName($name);

		$this->response->setStatus(200);
		$this->response->setHeader('Location', 'http://t3v5/index_dev.php/typo3/service/v1/sites/' . $site->getId() . '.json');
	}
}
?>