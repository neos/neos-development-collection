<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::Service::Controller;

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
 * @version $Id:F3::TYPO3::Controller::Page.php 262 2007-07-13 10:51:44Z robert $
 */

/**
 * The "Sites" service
 *
 * @package TYPO3
 * @subpackage Service
 * @version $Id:F3::TYPO3::Controller::Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class SitesController extends F3::FLOW3::MVC::Controller::RESTController {

	/**
	 * @var F3::TYPO3::Domain::Model::SiteRepository
	 */
	protected $siteRepository;

	/**
	 * Injects the site repository
	 *
	 * @param F3::TYPO3::Domain::Model::SiteRepository $siteRepository A reference to the site repository
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSiteRepository(F3::TYPO3::Domain::Model::SiteRepository $siteRepository) {
		$this->siteRepository = $siteRepository;
	}

	/**
	 * Initializes the arguments of this controller
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeArguments() {
		parent::initializeArguments();

		$this->arguments->addNewArgument('name', 'Text');
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
	 * @return string The status message
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createAction() {
		$site = $this->componentFactory->getComponent('F3::TYPO3::Domain::Model::Site');
		$site->setName($this->arguments['name']->getValue());
		$this->siteRepository->add($site);

		$this->response->setStatus(201);
		$this->response->setHeader('Location', 'http://t3v5/index_dev.php/typo3/service/v1/sites/' . $site->getId() . '.json');
	}

	/**
	 * Updates an existing site
	 *
	 * @return string The status message
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function updateAction() {
		$site = $this->siteRepository->findById($this->arguments['id']);
		if ($site === NULL) $this->throwStatus(404, NULL, 'Unknown Site');
		if ($this->arguments['name']->getValue() !== NULL) $site->setName($this->arguments['name']->getValue());

		$this->response->setStatus(200);
		$this->response->setHeader('Location', 'http://t3v5/index_dev.php/typo3/service/v1/sites/' . $site->getId() . '.json');
	}
}
?>