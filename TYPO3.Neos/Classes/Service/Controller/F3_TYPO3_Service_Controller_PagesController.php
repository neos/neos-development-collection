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
 *
 *
 * @package TYPO3
 * @subpackage Service
 * @version $Id:F3::TYPO3::Controller::Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PagesController extends F3::FLOW3::MVC::Controller::ActionController {

	/**
	 * @var F3::TYPO3::Domain::Model::PageRepository
	 */
	protected $pageRepository;

	/**
	 * Injects the page repository
	 *
	 * @param F3::TYPO3::Domain::Model::PageRepository $pageRepository A reference to the page repository
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPageRepository(F3::TYPO3::Domain::Model::PageRepository $pageRepository) {
		$this->pageRepository = $pageRepository;
	}

	/**
	 * Initializes this pages controller
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeController() {
	}

	/**
	 * Forwards the request to the listAction()
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function indexAction() {
		$this->forward('list');
	}

	/**
	 * Lists available pages from the repository
	 *
	 * @return string Output of the list view
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function listAction() {
		$pages = $this->pageRepository->findAll();
		$this->view->setPages($pages);
		return $this->view->render();
	}
}
?>