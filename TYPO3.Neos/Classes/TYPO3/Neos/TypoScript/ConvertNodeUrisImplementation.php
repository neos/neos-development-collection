<?php
namespace TYPO3\Neos\TypoScript;

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
use TYPO3\Neos\Domain\Exception;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;

/**
 * A TypoScript Object that converts Node references in the format "node://<UUID>" to proper URIs
 *
 * Usage::
 *
 *   someTextProperty.@process.1 = TYPO3.Neos:ConvertNodeUris
 */
class ConvertNodeUrisImplementation extends AbstractTypoScriptObject {

	const PATTERN_NODE_URIS = '/node:\/\/(([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12})/';

	/**
	 * The string to be processed
	 *
	 * @return string
	 */
	public function getValue() {
		return $this->tsValue('value');
	}

	/**
	 * Evaluate this TypoScript object and return the result
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function evaluate() {
		$text = $this->getValue();
		if (!is_string($text)) {
			throw new Exception(sprintf('Only strings can be processed by this TypoScript object, given: "%s".', gettype($text)), 1382624080);
		}
		$currentContext = $this->tsRuntime->getCurrentContext();
		$node = $currentContext['node'];
		if (!$node instanceof NodeInterface) {
			throw new Exception(sprintf('The current node must be an instance of NodeInterface, given: "%s".', gettype($text)), 1382624087);
		}
		if ($node->getContext()->getWorkspace()->getName() !== 'live') {
			return $text;
		}
		$self = $this;
		return preg_replace_callback(self::PATTERN_NODE_URIS, function(array $matches) use ($self, $node) {
			return $self->convertNodeIdentifierToUri($matches[1], $node);
		}, $text);
	}

	/**
	 * Converts the given node identifier (UUID) with a proper URI pointing to the target node - or with an empty string if the target node was not found in the current workspace
	 *
	 * @param $nodeIdentifier
	 * @param NodeInterface $contextNode
	 * @return string
	 */
	public function convertNodeIdentifierToUri($nodeIdentifier, NodeInterface $contextNode) {
		$targetNode = $contextNode->getContext()->getNodeByIdentifier($nodeIdentifier);
		if ($targetNode === NULL) {
			return '';
		}
		$uriBuilder = $this->tsRuntime->getControllerContext()->getUriBuilder();
		return $uriBuilder->setFormat('html')->uriFor('show', array('node' => $targetNode), 'Frontend\\Node', 'TYPO3.Neos');
	}
}
?>