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
 * The expression based node label generator that is used as default if a label expression is configured.
 *
 */
class ExpressionBasedNodeLabelGenerator implements NodeLabelGeneratorInterface {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Eel\EelEvaluatorInterface
	 */
	protected $eelEvaluator;

	/**
	 * @Flow\Inject(setting="labelGenerator.eel.defaultContext")
	 * @var array
	 */
	protected $defaultContextConfiguration;

	/**
	 * @var string
	 */
	protected $expression = '${(node.nodeType.label ? node.nodeType.label : node.nodeType.name) + \' (\' + node.name + \')\'}';

	/**
	 * @return string
	 */
	public function getExpression() {
		return $this->expression;
	}

	/**
	 * @param string $expression
	 */
	public function setExpression($expression) {
		$this->expression = $expression;
	}

	/**
	 * @return void
	 */
	public function initializeObject() {
		if ($this->eelEvaluator instanceof \TYPO3\Flow\Object\DependencyInjection\DependencyProxy) {
			$this->eelEvaluator->_activateDependency();
		}
	}

	/**
	 * Render a node label
	 *
	 * @param NodeInterface $node
	 * @param boolean $crop
	 * @return string
	 */
	public function getLabel(NodeInterface $node, $crop = TRUE) {
		$label = \TYPO3\Eel\Utility::evaluateEelExpression($this->getExpression(), $this->eelEvaluator, array('node' => $node), $this->defaultContextConfiguration);

		if ($crop === FALSE) {
			return $label;
		}

		$croppedLabel = \TYPO3\Flow\Utility\Unicode\Functions::substr($label, 0, NodeInterface::LABEL_MAXIMUM_CHARACTERS);
		return $croppedLabel . (strlen($croppedLabel) < strlen($label) ? ' …' : '');
	}
}
