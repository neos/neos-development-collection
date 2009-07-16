<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Service\Controller;

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
 * The "Structure Nodes" service
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class StructureNodesController extends \F3\FLOW3\MVC\Controller\RESTController {

	/**
	 * @var \F3\TYPO3\Domain\Repository\StructureNodeRepository
	 */
	protected $structureNodeRepository;

	/**
	 * Injects the structure node repository
	 *
	 * @param \F3\TYPO3\Domain\Repository\StructureNodeRepository $structureNodeRepository A reference to the structure node repository
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectStructureNodeRepository(\F3\TYPO3\Domain\Repository\StructureNodeRepository $structureNodeRepository) {
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
#		$node = $this->objectFactory->create('F3\TYPO3\Domain\Model\Structure\StructureNode');
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
	protected function convertStructureNodeToArray(\F3\TYPO3\Domain\Model\Structure\StructureNode $structureNode) {
		$childNodes = array();
		foreach ($structureNode->getChildNodes() as $childNode) {
			$childNodes[] = $this->convertStructureNodeToArray($childNode);
		}

		$content = $structureNode->getContent();
		if ($content !== NULL) {
			$contentId = $content->getId();
			$contentClass = ($content instanceof \F3\FLOW3\AOP\ProxyInterface) ? $content->FLOW3_AOP_Proxy_getProxyTargetClassName() : get_class($content);
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