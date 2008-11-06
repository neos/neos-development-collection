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
		$this->view->assign('baseURI', $this->request->getBaseURI());
		return $this->view->render();
	}

	/**
	 * Sets up some data for playing around ...
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setupAction() {

			// Create structure nodes
		$structureNode1 = $this->componentFactory->create('F3::TYPO3::Domain::Model::StructureNode');
		$structureNode1a = $this->componentFactory->create('F3::TYPO3::Domain::Model::StructureNode');
		$structureNode1aa = $this->componentFactory->create('F3::TYPO3::Domain::Model::StructureNode');
		$structureNode1b = $this->componentFactory->create('F3::TYPO3::Domain::Model::StructureNode');
		$structureNode1c = $this->componentFactory->create('F3::TYPO3::Domain::Model::StructureNode');
		$structureNode1d = $this->componentFactory->create('F3::TYPO3::Domain::Model::StructureNode');

		$structureNode1->addChildNode($structureNode1a);
		$structureNode1->addChildNode($structureNode1b);
		$structureNode1->addChildNode($structureNode1c);
		$structureNode1->addChildNode($structureNode1d);
		$structureNode1a->addChildNode($structureNode1aa);

			// Create pages
		$page1 = $this->componentFactory->create('F3::TYPO3::Domain::Model::Content::Page', 'Page 1');
		$structureNode1->setContent($page1);

		$page1a = $this->componentFactory->create('F3::TYPO3::Domain::Model::Content::Page', 'Page 1a');
		$structureNode1a->setContent($page1a);

		$page1aa = $this->componentFactory->create('F3::TYPO3::Domain::Model::Content::Page', 'Page 1aa');
		$structureNode1aa->setContent($page1aa);

		$page1b = $this->componentFactory->create('F3::TYPO3::Domain::Model::Content::Page', 'Page 1b');
		$structureNode1b->setContent($page1b);

			// Create text content
		$text1c = $this->componentFactory->create('F3::TYPO3::Domain::Model::Content::Text', 'Text 1c');
		$structureNode1c->setContent($text1c);

		$text1d = $this->componentFactory->create('F3::TYPO3::Domain::Model::Content::Text', 'Text 1d');
		$structureNode1d->setContent($text1d);

			// Create a sample site
		$site = $this->componentFactory->create('F3::TYPO3::Domain::Model::Site');
		$site->setName('typo3.org');
		$site->setRootStructureNode($structureNode1);

		$siteRepository = $this->componentManager->getComponent('F3::TYPO3::Domain::Model::SiteRepository');
		$siteRepository->add($site);

			// Create a second sample site
		$site = $this->componentFactory->create('F3::TYPO3::Domain::Model::Site');
		$site->setName('flow3.typo3.org');
		$siteRepository->add($site);

		return 'Created some data for playing around.';
	}
}
?>