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
 * @package CRAdmin
 * @version $Id$
 */

/**
 * The default CRAdmin controller
 *
 * @package CRAdmin
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Controller_Admin extends F3_FLOW3_MVC_Controller_ActionController {

	/**
	 * @var F3_PHPCR_SessionInterface
	 */
	protected $session;

	/**
	 * Initializes this controller
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeController() {
		$this->supportedRequestTypes = array('F3_FLOW3_MVC_Web_Request');
		$repository = $this->componentFactory->getComponent('F3_PHPCR_RepositoryInterface');

		$this->session = $repository->login();
	}

	/**
	 * The default action of this phonebook controller
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function defaultAction() {
		return $this->showFullTreeAction();
	}

	/**
	 * Displays a full tree of the CR content
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function showFullTreeAction() {
		$view = $this->componentFactory->getComponent('F3_TYPO3CR_View_Admin_FullTree');
		$view->setRootNode($this->session->getRootNode());
		return $view->render();
	}

}
?>