<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
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
 * @deprecated Since 1.2 You should implement the NodeLabelGeneratorInterface now.
 */
class FallbackNodeDataLabelGenerator implements NodeDataLabelGeneratorInterface {

	/**
	 * Render a node label
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\AbstractNodeData $nodeData
	 * @param boolean $crop
	 * @return string
	 */
	public function getLabel(AbstractNodeData $nodeData, $crop = TRUE) {
		if ($nodeData->hasProperty('title') === TRUE && $nodeData->getProperty('title') !== '') {
			$label = strip_tags($nodeData->getProperty('title'));
		} elseif ($nodeData->hasProperty('text') === TRUE && $nodeData->getProperty('text') !== '') {
			$label = strip_tags($nodeData->getProperty('text'));
		} else {
			$label = ($nodeData->getNodeType()->getLabel() ?: $nodeData->getNodeType()->getName()) . ' (' . $nodeData->getName() . ')';
		}

		if ($crop === FALSE) {
			return $label;
		}

		$croppedLabel = \TYPO3\Flow\Utility\Unicode\Functions::substr($label, 0, NodeInterface::LABEL_MAXIMUM_CHARACTERS);
		return $croppedLabel . (strlen($croppedLabel) < strlen($label) ? ' …' : '');
	}
}
