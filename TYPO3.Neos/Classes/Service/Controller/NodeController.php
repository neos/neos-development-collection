<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Service\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The Node Controller
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU General Public License, version 3 or later
 */
class NodeController extends \F3\FLOW3\MVC\Controller\RESTController {

	/**
	 * @var string
	 */
	protected $resourceArgumentName = 'node';

	/**
	 * @var array
	 */
	protected $viewFormatToObjectNameMap = array(
		 'html' => 'F3\Fluid\View\TemplateView',
		 'extdirect' => 'F3\ExtJS\ExtDirect\View',
		 'json' => 'F3\FLOW3\MVC\View\JsonView',
	);

	/**
	 * Select special error action
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeAction() {
		if ($this->request->getFormat() == 'extdirect') {
			$this->errorMethodName = 'extErrorAction';
		}
	}

	/**
	 * Shows the specified node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @return string View output for the specified node
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function showAction(\F3\TYPO3CR\Domain\Model\Node $node) {
		$contentContext = $node->getContext();

		$type = 'default';
		switch ($this->request->getFormat()) {
			case 'extdirect' :
			case 'json' :
				$this->view->setConfiguration(
					array('value' => array('data' => array(
						 'only' => array('name', 'path', 'properties'),
						 'descend' => array('properties' => array()))
					)));
				$this->view->assign('value',
					array(
						'data' => $node,
						'success' => TRUE,
					)
				);
			break;
			case 'html' :
				return 'This service currently does not support HTML output.';
		}
	}

	/**
	 * Update a node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node The cloned, updated node
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function updateAction(\F3\TYPO3CR\Domain\Model\Node $node) {
		$this->contentRepository->update($node);

		switch ($this->request->getFormat()) {
			case 'extdirect' :
			case 'json' :
				$this->view->assign('value',
					array(
						'success' => TRUE
					)
				);
				break;
		}
	}

	/**
	 * Delete a node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node The node
	 * @return void
	 * @author Robert Lemke
	 * @extdirect
	 */
	public function deleteAction(\F3\TYPO3CR\Domain\Model\Node $node) {
		$node->remove();

		switch ($this->request->getFormat()) {
			case 'extdirect' :
			case 'json' :
				$this->view->assign('value',
					array(
						'success' => TRUE
					)
				);
			break;
			case 'html' :
				$this->redirect('index');
			break;
		}
	}

	/**
	 * A preliminary error action for handling validation errors
	 * by assigning them to the ExtDirect View that takes care of
	 * converting them.
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function extErrorAction() {
		$this->view->assignErrors($this->argumentsMappingResults->getErrors());
	}

}
?>