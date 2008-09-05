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
class F3_TYPO3_Service_Controller_Sites extends F3_FLOW3_MVC_Controller_ActionController {

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
	 * Initializes this controller
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeController() {
		$this->arguments->addNewArgument('identifier', 'UUID');
	}

	/**
	 * Forwards the request to the respective actions
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function defaultAction() {
		switch ($this->request->getMethod()) {
			case 'GET' : $this->forward('list');
		}
		$this->throwStatus(400);
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
	 * Lists all first-level pages of the specified site
	 *
	 * @return string Output of the view
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function pagesAction() {
		if ($this->argumentMappingResults->hasErrors()) $this->throwStatus(400, 'Invalid Arguments', '<p>The specified arguments were invalid.</p>');
		$siteIdentifier = $this->arguments['identifier']->getValue();
		$site = $this->siteRepository->findByIdentifier($siteIdentifier);
		if ($site === NULL) $this->throwStatus(404, NULL, '<p>The specified site does not exist.</p>');
		return 'OK';
	}
}
?>