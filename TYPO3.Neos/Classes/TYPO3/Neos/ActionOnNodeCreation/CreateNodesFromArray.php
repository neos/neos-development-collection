<?php
namespace TYPO3\Neos\ActionOnNodeCreation;

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
use TYPO3\Neos\Service\NodeOperations;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Create nodes from source array
 */
class CreateNodesFromArray extends AbstractActionOnNodeCreation {

	/**
	 * @Flow\Inject
	 * @var NodeOperations
	 */
	protected $nodeOperations;

	/**
	 * Execute the action
	 *
	 * @param NodeInterface $node
	 * @param array $options
	 * @return void
	 */
	public function execute(NodeInterface $node, array $options) {
		if (isset($options['nodePath']) && !empty($options['nodePath'])) {
			$referenceNode = $node->getNode($options['nodePath']);
		} else {
			$referenceNode = $node;
		}

		$defaultNodeData = $options['nodeData'];

		if (is_string($options['dynamicPropertySource'])) {
			$options['dynamicPropertySource'] = json_decode($options['dynamicPropertySource'], TRUE);
		}
		foreach ($options['dynamicPropertySource'] as $value) {
			$nodeData = $defaultNodeData;
			$nodeData['properties'][$options['dynamicProperty']] = $value;
			$this->nodeOperations->create($referenceNode, $nodeData, 'into');
		}
	}

}
