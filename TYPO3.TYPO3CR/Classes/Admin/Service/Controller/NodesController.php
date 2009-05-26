<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Admin\Service\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
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
 * @package TYPO3CR
 * @subpackage Admin
 * @version $Id$
 */

/**
 * The "Nodes" service
 *
 * @package TYPO3CR
 * @subpackage Admin
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NodesController extends \F3\FLOW3\MVC\Controller\RESTController {

	/**
	 * \F3\PHPCR\SessionInterface
	 */
	protected $contentRepositorySession;

	/**
	 * @var \F3\PHPCR\NodeInterface
	 */
	protected $rootNode;

	/**
	 * Injects a Content Repository instance
	 *
	 * @param \F3\PHPCR\RepositoryInterface $contentRepository
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectContentRepository(\F3\PHPCR\RepositoryInterface $contentRepository) {
		$this->contentRepositorySession = $contentRepository->login();
		$this->rootNode = $this->contentRepositorySession->getRootNode();
	}

	/**
	 * Lists available structure nodes from the repository
	 *
	 * @return string Output of the list view
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function listAction() {
		$this->view->nodes = array($this->convertNodeToArray($this->rootNode));
		return $this->view->render();
	}

	/**
	 * Shows properties of a specific structure node
	 *
	 * @return string Output of the show view
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function showAction() {
		try {
			$node = $this->contentRepositorySession->getNodeByIdentifier($this->arguments['id']->getValue());
			$properties = $node->getProperties();
		} catch(\F3\PHPCR\ItemNotFoundException $e) {
			$this->throwStatus(404);
		}

		$data = array();
		foreach ($properties as $property) {
			try {
				$data[] = array(
					'name' => $property->getName(),
					'type' => \F3\PHPCR\PropertyType::nameFromValue($property->getType()),
					'value' => $property->getValue()->getString()
				);
			} catch (\F3\PHPCR\ValueFormatException $e) {
				$value = '';
				$propertyValues = $property->getValues();
				foreach ($propertyValues as $propertyValue) {
					$value[] = $propertyValue->getString();
				}
				$data[] = array(
					'name' => $property->getName(),
					'type' => \F3\PHPCR\PropertyType::nameFromValue($property->getType()) . '[]',
					'value' => $value
				);
			}
		}

		$this->view->node = array('properties' => $data);
		return $this->view->render();
	}

	/**
	 * Creates a new structure node
	 *
	 * @return string The status message
	 */
	public function createAction() {
		$this->throwStatus(501);
	}

	/**
	 * Updates an existing structure node
	 *
	 * @return string The status message
	 */
	public function updateAction() {
		$this->throwStatus(501);
	}

	/**
	 * Deletes a structure node
	 *
	 * @return string
	 */
	public function deleteAction() {
		try {
			$node = $this->contentRepositorySession->getNodeByIdentifier($this->arguments['id']->getValue());
		} catch(\F3\PHPCR\ItemNotFoundException $e) {
			$this->throwStatus(404);
		}

		$node->remove();
		$this->contentRepositorySession->save();
	}



	/**
	 * Returns an array representing the given node.
	 *
	 * @param \F3\PHPCR\NodeInterface $node
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function convertNodeToArray(\F3\PHPCR\NodeInterface $node) {
		$nodeArray = array(
			'id' => $node->getIdentifier(),
			'text' => $node->getName(),
			'leaf' => !$node->hasNodes(),
			'children' => array()
		);

		$childNodes = $node->getNodes();
		foreach ($childNodes as $node) {
			$nodeArray['children'][] = $this->convertNodeToArray($node);
		}

		return $nodeArray;
	}

}
?>