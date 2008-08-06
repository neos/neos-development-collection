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
 * @package PhoneBookTutorial
 * @version $Id$
 */

/**
 * A phone book entries view
 *
 * @package PhoneBookTutorial
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_View_Admin_FullTree extends F3_FLOW3_MVC_View_AbstractView {

	/**
	 * @var F3_PHPCR_NodeInterface
	 */
	protected $rootNode;

	/**
	 * Enter description here...
	 *
	 * @param F3_PHPCR_NodeInterface $node
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setRootNode(F3_PHPCR_NodeInterface $node) {
		$this->rootNode = $node;
	}

	/**
	 * Renders a tree
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function render() {
		$HTML = '<h1>Full Tree</h1>';
		$HTML .= '<ul style="list-style-type:none;"><li style="border:solid 1px #ccc; padding:2px; margin:2px;">[root node] (' . $this->rootNode->getPrimaryNodeType()->getName() . ', ' . $this->rootNode->getIdentifier() . ')';
		$HTML .= $this->renderProperties($this->rootNode->getProperties());
		$HTML .= $this->renderSubNodes($this->rootNode->getNodes());
		$HTML .= '</li></ul>';
		return $HTML;
	}

	/**
	 * Renders a tree
	 *
	 * @param F3_PHPCR_NodeIteratorInterface $nodes
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function renderSubNodes(F3_PHPCR_NodeIteratorInterface $nodes) {
		$HTML = '<ul style="list-style-type:none;">';
		foreach ($nodes as $node) {
			$HTML .= '<li style="border:solid 1px #ccc; padding:2px; margin:2px;">' . $node->getName() . ' (' . $node->getPrimaryNodeType()->getName() . ', ' . $node->getIdentifier() . ')';
			$HTML .= $this->renderProperties($node->getProperties());
			$HTML .= $this->renderSubNodes($node->getNodes());
			$HTML .= '</li>';
		}
		return $HTML . '</ul>';
	}

	/**
	 * Renders a tree
	 *
	 * @param F3_PHPCR_PropertyIteratorInterface $properties
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function renderProperties(F3_PHPCR_PropertyIteratorInterface $properties) {
		$HTML = '<ul>';
		foreach ($properties as $property) {
			$HTML .= '<li>' . $property->getName() . ' => ' . $this->propertyToString($property) . ' (' . F3_PHPCR_PropertyType::nameFromValue($property->getType()) . ')</li>';
		}
		return $HTML . '</ul>';
	}

	/**
	 * Helper to display single- and multi-valued properties
	 *
	 * @param F3_PHPCR_PropertyInterface $property
	 * @return mixed
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function propertyToString(F3_PHPCR_PropertyInterface $property) {
		if ($property->getType() === F3_PHPCR_PropertyType::DATE) {
			return $property->getDate();
		}
		try {
			return $property->getString();
		} catch (F3_PHPCR_ValueFormatException $e) {
			return '[array]';
		}
	}
}
?>