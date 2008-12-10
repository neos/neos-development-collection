<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Admin\Service\Controller;

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
 * @package TYPO3CR
 * @subpackage Admin
 * @version $Id:\F3\TYPO3\Controller\Page.php 262 2007-07-13 10:51:44Z robert $
 */

/**
 * The "Nodes" service
 *
 * @package TYPO3CR
 * @subpackage Admin
 * @version $Id:\F3\TYPO3\Controller\Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class NodesController extends \F3\FLOW3\MVC\Controller\RESTController {

	/**
	 * \F3\PHPCR\SessionInterface
	 */
	protected $session;

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
		$this->session = $contentRepository->login();
		$this->rootNode = $this->session->getRootNode();
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
			$node = $this->session->getNodeByIdentifier($this->arguments['id']->getValue());
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
			$node = $this->session->getNodeByIdentifier($this->arguments['id']->getValue());
		} catch(\F3\PHPCR\ItemNotFoundException $e) {
			$this->throwStatus(404);
		}

		$node->remove();
		$this->session->save();
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