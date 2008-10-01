<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::Backend::Controller;

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
 * @version $Id:F3::TYPO3::Controller::Page.php 262 2007-07-13 10:51:44Z robert $
 */

/**
 * The TYPO3 Backend controller
 *
 * @package TYPO3
 * @version $Id:F3::TYPO3::Controller::Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class DefaultController extends F3::FLOW3::MVC::Controller::ActionController {

	/**
	 * @var array Only Web Requests are supported
	 */
	protected $supportedRequestTypes = array('F3::FLOW3::MVC::Web::Request');

	/**
	 * Default action of the backend controller.
	 * Forwards the request to the default module.
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function indexAction() {
		return $this->view->render();
	}

	/**
	 * Sets up some data for playing around ...
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setupAction() {
		$structureNodeRepository = $this->componentFactory->getComponent('F3::TYPO3::Domain::Model::StructureNodeRepository');

		$structureNode4 = $this->componentFactory->getComponent('F3::TYPO3::Domain::Model::StructureNode');
		$structureNode3 = $this->componentFactory->getComponent('F3::TYPO3::Domain::Model::StructureNode');
		$structureNode2 = $this->componentFactory->getComponent('F3::TYPO3::Domain::Model::StructureNode');
		$structureNode1 = $this->componentFactory->getComponent('F3::TYPO3::Domain::Model::StructureNode');

		$structureNode1->addChildNode($structureNode2);
		$structureNode2->addChildNode($structureNode3);
		$structureNode1->addChildNode($structureNode4);
		$structureNodeRepository->add($structureNode1);

		$site = $this->componentFactory->getComponent('F3::TYPO3::Domain::Model::Site');
		$site->setName('typo3.org');
		$site->setRootStructureNode($structureNode1);

		$siteRepository = $this->componentFactory->getComponent('F3::TYPO3::Domain::Model::SiteRepository');
		$siteRepository->add($site);

		$site = $this->componentFactory->getComponent('F3::TYPO3::Domain::Model::Site');
		$site->setName('flow3.typo3.org');
		$siteRepository->add($site);

		return 'Created some data for playing around.';
	}
}
?>