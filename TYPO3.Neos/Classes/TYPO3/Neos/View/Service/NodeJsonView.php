<?php
namespace TYPO3\Neos\View\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\View\JsonView;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A view specialised on a JSON representation of Nodes.
 *
 * This view is used by the service controllers in TYPO3\Neos\Controller\Service\
 *
 * @Flow\Scope("prototype")
 */
class NodeJsonView extends JsonView {

	/**
	 * Assigns a node to the view.
	 *
	 * @param NodeInterface $node The node to render
	 * @param array $propertyNames Optional list of property names to include in the JSON output
	 * @return void
	 */
	public function assignNode(NodeInterface $node, array $propertyNames = array('name', 'path', 'identifier', 'properties', 'nodeType')) {
		$this->setConfiguration(
			array(
				'value' => array(
					'_only' => $propertyNames,
					'_descend' => array('properties' => $propertyNames)
				)
			)
		);

		$this->assign('value', $node);
	}

}