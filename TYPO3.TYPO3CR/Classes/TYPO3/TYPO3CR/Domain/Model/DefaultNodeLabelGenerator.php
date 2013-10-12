<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * The default node label generator; used if no-other is configured
 *
 * @Flow\Scope("singleton")
 */
class DefaultNodeLabelGenerator implements NodeLabelGeneratorInterface {

	/**
	 * Render a node label
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\AbstractNodeData $nodeData
	 * @return string
	 */
	public function getLabel(AbstractNodeData $nodeData) {
		if ($nodeData->hasProperty('title') === TRUE && $nodeData->getProperty('title') !== '') {
			$label = strip_tags($nodeData->getProperty('title'));
		} elseif ($nodeData->hasProperty('text') === TRUE && $nodeData->getProperty('text') !== '') {
			$label = strip_tags($nodeData->getProperty('text'));
		} else {
			$label = '(' . $nodeData->getNodeType()->getName() . ') ' . $nodeData->getName();
		}

		$croppedLabel = \TYPO3\Flow\Utility\Unicode\Functions::substr($label, 0, NodeInterface::LABEL_MAXIMUM_CHARACTERS);
		return $croppedLabel . (strlen($croppedLabel) < strlen($label) ? ' …' : '');
	}
}
