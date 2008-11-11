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
 * The "Structure Nodes" service
 *
 * @package TYPO3
 * @subpackage Service
 * @version $Id:F3::TYPO3::Controller::Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class StructureNodesController extends F3::FLOW3::MVC::Controller::RESTController {

	/**
	 * @var F3::TYPO3::Domain::Model::StructureNodeRepository
	 */
	protected $structureNodeRepository;

	/**
	 * Injects the structure node repository
	 *
	 * @param F3::TYPO3::Domain::Model::StructureNodeRepository $structureNodeRepository A reference to the structure node repository
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectStructureNodeRepository(F3::TYPO3::Domain::Model::StructureNodeRepository $structureNodeRepository) {
		$this->structureNodeRepository = $structureNodeRepository;
	}

	/**
	 * Lists available structure nodes from the repository
	 *
	 * @return string Output of the list view
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function listAction() {
		$preparedStructureNodes = array();
		foreach ($this->structureNodeRepository->findAll() as $structureNode) {
			$preparedStructureNodes[] = $this->convertStructureNodeToArray($structureNode);
		}
		$this->view->structureNodes = $preparedStructureNodes;
		return $this->view->render();
	}

	/**
	 * Shows properties of a specific structure node
	 *
	 * @return string Output of the show view
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function showAction() {
		$structureNode = $this->structureNodeRepository->findById($this->arguments['id']->getValue());
		if ($structureNode === NULL) $this->throwStatus(404);
		$this->view->structureNode = $this->convertStructureNodeToArray($structureNode);
		return $this->view->render();
	}

	/**
	 * Creates a new structure node
	 *
	 * @return string The status message
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createAction() {
		$this->throwStatus(501);
#		$node = $this->objectFactory->create('F3::TYPO3::Domain::Model::StructureNode');
#		$node->structureNodeRepository->add($node);

#		$this->response->setStatus(201);
#		$this->response->setHeader('Location', 'http://t3v5/index_dev.php/typo3/service/v1/structurenodes/' . $node->getId() . '.json');
	}

	/**
	 * Updates an existing structure node
	 *
	 * @return string The status message
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function updateAction() {
		$this->throwStatus(501);

		$structureNode = $this->structureNodeRepository->findById($this->arguments['id']->getValue());
		if ($structureNode === NULL) $this->throwStatus(404);

		$this->response->setStatus(200);
#		$this->response->setHeader('Location', 'http://t3v5/index_dev.php/typo3/service/v1/sites/' . $site->getId() . '.json');
	}

	/**
	 * Deletes a structure node
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function deleteAction() {
		$this->throwStatus(501);
	}

	/**
	 * Converts an array of structure node objects into an array with simple types
	 * suitable for a view
	 *
	 * @param array An array of structure node objects
	 * @return array The converted structure nodes
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function convertStructureNodeToArray(F3::TYPO3::Domain::Model::StructureNode $structureNode) {
		$childNodes = array();
		foreach ($structureNode->getChildNodes() as $childNode) {
			$childNodes[] = $this->convertStructureNodeToArray($childNode);
		}

		$content = $structureNode->getContent();
		if ($content !== NULL) {
			$contentId = $content->getId();
			$contentClass = ($content instanceof F3::FLOW3::AOP::ProxyInterface) ? $content->AOPProxyGetProxyTargetClassName() : get_class($content);
		} else {
			$contentId = '';
			$contentClass = '';
		}

		$structureNodeArray = array(
			'id' => $structureNode->getId(),
			'label' => $structureNode->getLabel(),
			'childNodes' => $childNodes,
			'hasChildNodes' => $structureNode->hasChildNodes(),
			'contentId' => $contentId,
			'contentClass' => $contentClass
		);
		return $structureNodeArray;
	}
}
?>