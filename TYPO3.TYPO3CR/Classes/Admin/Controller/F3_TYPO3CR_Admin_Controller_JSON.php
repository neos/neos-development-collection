<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR::Admin::Controller;

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
 * @version $Id$
 */

/**
 * The default Nodes controller
 *
 * @package TYPO3CR
 * @subpackage Admin
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class JSON extends F3::FLOW3::MVC::Controller::ActionController {

	/**
	 * @var F3::PHPCR::SessionInterface
	 */
	protected $session;

	/**
	 * @var F3::PHPCR::NodeInterface
	 */
	protected $node;

	/**
	 * Injects a Content Repository instance
	 *
	 * @param F3::PHPCR::RepositoryInterface $contentRepository
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectContentRepository(F3::PHPCR::RepositoryInterface $contentRepository) {
		$this->session = $contentRepository->login();
		$this->rootNode = $this->session->getRootNode();
	}

	/**
	 * Initializes this controller
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeController() {
		$this->supportedRequestTypes = array('F3::FLOW3::MVC::Web::Request');
		$this->arguments->addNewArgument('node');
#		if ($this->request->getFormat() != 'json') {
#			throw new F3::FLOW3::MVC::Exception::InvalidFormat('Only JSON requests, please... You asked for ' . $this->request->getFormat(), 1218198618);
#		}
	}

	/**
	 * Do some preparations for handling the action.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeAction() {
		$requestedNode = $this->request->getArgument('node');
		if ($requestedNode == 'ROOT') {
			$this->node = $this->rootNode;
		} else {
			$this->node = $this->session->getNodeByIdentifier($requestedNode);
		}
	}

	/**
	 * The getNodes action of this controller
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getNodesAction() {
		$data = array();
		$nodes = $this->node->getNodes();
		foreach ($nodes as $node) {
			$data[] = array(
				'id' => $node->getIdentifier(),
				'text' => $node->getName(),
				'leaf' => !$node->hasNodes()
			);
		}

		return json_encode($data);
	}


	/**
	 * The getProperties action of this controller
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPropertiesAction() {
		$data = array();
		$properties = $this->node->getProperties();

		foreach ($properties as $property) {
			try {
				$data[] = array(
					'name' => $property->getName(),
					'type' => F3::PHPCR::PropertyType::nameFromValue($property->getType()),
					'value' => $property->getValue()->getString()
				);
			} catch (F3::PHPCR::ValueFormatException $e) {
				$value = '';
				$propertyValues = $property->getValues();
				foreach ($propertyValues as $propertyValue) {
					$value[] = $propertyValue->getString();
				}
				$data[] = array(
					'name' => $property->getName(),
					'type' => F3::PHPCR::PropertyType::nameFromValue($property->getType()) . '[]',
					'value' => $value
				);
			}
		}

		return json_encode(array('properties' => $data));
	}

}
?>