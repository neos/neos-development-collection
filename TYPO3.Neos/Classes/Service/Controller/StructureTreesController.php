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
 * @package TYPO3
 * @subpackage Service
 * @version $Id$
 */

/**
 * The "Structure Trees" service
 *
 * @package TYPO3
 * @subpackage Service
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class StructureTreesController extends \F3\FLOW3\MVC\Controller\RESTController {

	/**
	 * @var \F3\TYPO3\Domain\Model\StructureNodeRepository
	 */
	protected $structureNodeRepository;

	/**
	 * Injects the structure node repository
	 *
	 * @param \F3\TYPO3\Domain\Model\StructureNodeRepository $structureNodeRepository The structure node repository
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectStructureNodeRepository(\F3\TYPO3\Domain\Model\StructureNodeRepository $structureNodeRepository) {
		$this->structureNodeRepository = $structureNodeRepository;
	}

	/**
	 * Initializes the arguments of this controller
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeArguments() {
		parent::initializeArguments();
	}

	/**
	 * The list action does not make sense for the Structure Tree service
	 *
	 * @return string An error message
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function listAction() {
		$this->throwStatus(400, NULL, 'Bad Request. You must specify a root node.');
	}

	/**
	 * Shows the structure tree starting with the structure node specified
	 * by the identifier argument
	 *
	 * @return string Rendered structure tree
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function showAction() {
		$rootStructureNode = $this->structureNodeRepository->findById($this->arguments['id']->getValue());
		if ($rootStructureNode === NULL) $this->throwStatus(404);

		$this->view->structureTree = array($this->buildStructureTreeArray($rootStructureNode));
		return $this->view->render();
	}

	/**
	 * The create action does not make sense and is not allowed.
	 *
	 * @return string An error message
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createAction() {
		$this->throwMethodNotAllowed();
	}

	/**
	 * The delete action does not make sense and is not allowed.
	 *
	 * @return string An error message
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function deleteAction() {
		$this->throwMethodNotAllowed();
	}

	/**
	 * The update action does not make sense and is not allowed.
	 *
	 * @return string An error message
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function updateAction() {
		$this->throwMethodNotAllowed();
	}

	/**
	 * Throws a Method Not Allowed Response
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function throwMethodNotAllowed() {
		$this->response->setHeader('Allow', 'GET');
		$this->throwStatus(405);
	}

	/**
	 * Recursively builds an array reflecting the structure tree starting at the
	 * specified node. This array can be well used in the view, as it contains all
	 * relevant data.
	 *
	 * @param \F3\TYPO3\Domain\Model\StructureNode $node The root node of the structure tree to build
	 * @param integer $maximumLevels The number of levels to build
	 * @param integer $currentLevel Used internally for keeping track of the current recursion level
	 * @param array $structureTreeArray Used internally for building the tree array
	 * @return array The structure tree as an array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildStructureTreeArray(\F3\TYPO3\Domain\Model\StructureNode $structureNode, $maximumLevels = 30, $currentLevel = 1, $structureTreeArray = array()) {

		$content = $structureNode->getContent();
		if ($content !== NULL) {
			$contentClass = ($content instanceof \F3\FLOW3\AOP\ProxyInterface) ? $content->FLOW3_AOP_Proxy_getProxyTargetClassName() : get_class($content);
		} else {
			$contentClass = '';
		}
		$structureTreeArray = array(
			'id' => $structureNode->getId(),
			'label' => $structureNode->getLabel(),
			'hasChildNodes' => $structureNode->hasChildNodes(),
			'childNodes' => array(),
			'contentClass' => $contentClass
		);

		if ($currentLevel < $maximumLevels) {
			foreach ($structureNode->getChildNodes() as $childNode) {
				$structureTreeArray['childNodes'][] = $this->buildStructureTreeArray($childNode, $maximumLevels, ($currentLevel + 1));
			}
		}

		return $structureTreeArray;
	}
}
?>