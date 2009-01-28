<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Frontend\Controller;

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
 * @subpackage Frontend
 * @version $Id$
 */

/**
 * TYPO3's frontend page controller
 *
 * @package TYPO3
 * @subpackage Frontend
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DefaultController extends \F3\FLOW3\MVC\Controller\ActionController {

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
	public function indexAction() {
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

#		$pages = $this->pageRepository->findByUUID($pageUUID);
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