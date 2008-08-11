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
 * @subpackage Frontend
 * @version $Id:F3_TYPO3_Controller_Page.php 262 2007-07-13 10:51:44Z robert $
 */

/**
 * TYPO3's frontend page controller
 *
 * @package TYPO3
 * @subpackage Frontend
 * @version $Id:F3_TYPO3_Controller_Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3_Frontend_Controller_Default extends F3_FLOW3_MVC_Controller_ActionController {

	/**
	 * @var F3_TYPO3_Domain_Model_PageRepository
	 */
	protected $pageRepository;

	/**
	 * Injects the page repository
	 *
	 * @param F3_TYPO3_Domain_Model_PageRepository $pageRepository The page repository
	 * @return void
	 */
	public function injectPageRepository(F3_TYPO3_Domain_Model_PageRepository $pageRepository) {
		$this->pageRepository = $pageRepository;
	}


	/**
	 * Initializes this controller
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeController() {
		$this->arguments->addNewArgument('page', 'UUID');
	}


	/**
	 * Alias for the "show" action
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function defaultAction() {
		$this->forward('show');
	}

	/**
	 * Shows the page specified in the "page" argument
	 *
	 * @return string View output for the specified page
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function showAction() {
		$this->prepareFake();

		$pageUUID = $this->arguments['page']->getValue();
		if ($pageUUID === NULL) return 'Invalid page uuid';

		$pages = $this->pageRepository->findByUUID($pageUUID);
		return 'TYPO3 Frontend: show()';
	}

	/**
	 * Prepares a fake set of pages etc. for testing
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function prepareFake() {
		$this->arguments['page']->setValue('e0675c97-ffbe-4559-88cb-2da57a6f3064');
	}
}
?>