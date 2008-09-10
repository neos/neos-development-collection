<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TYPO3
 * @subpackage Service
 * @version $Id:F3_TYPO3_Controller_Page.php 262 2007-07-13 10:51:44Z robert $
 */

/**
 * The "Sites" service
 *
 * @package TYPO3
 * @subpackage Service
 * @version $Id:F3_TYPO3_Controller_Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3_Service_Controller_Sites extends F3_FLOW3_MVC_Controller_RESTController {

	/**
	 * @var F3_TYPO3_Domain_Model_SiteRepository
	 */
	protected $siteRepository;

	/**
	 * Injects the site repository
	 *
	 * @param F3_TYPO3_Domain_Model_SiteRepository $siteRepository A reference to the site repository
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSiteRepository(F3_TYPO3_Domain_Model_SiteRepository $siteRepository) {
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
		$this->view->setSites($sites);
		return $this->view->render();
	}

	/**
	 * Shows properties of a specific site
	 *
	 */
	public function showAction() {
		$site = $this->siteRepository->findByIdentifier($this->arguments['identifier']);
		if ($site === NULL) $this->throwStatus(404);
		$this->view->setSite($site);
		return $this->view->render();
	}
}
?>