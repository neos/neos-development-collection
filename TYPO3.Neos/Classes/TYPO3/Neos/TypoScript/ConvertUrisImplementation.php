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
 * A TypoScript Object that converts link references in the format "<type>://<UUID>" to proper URIs
 *
 * Right now node://<UUID> and asset://<UUID> are supported URI schemes.
 *
 * Usage::
 *
 *   someTextProperty.@process.1 = TYPO3.Neos:ConvertUris
 */
class ConvertUrisImplementation extends AbstractTypoScriptObject {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Media\Domain\Repository\AssetRepository
	 */
	protected $assetRepository;

	/**
	 * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
	 * @Flow\Inject
	 */
	protected $resourcePublisher;

	const PATTERN_NODE_URIS = '/(node|asset):\/\/(([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12})/';

	/**
	 * The string to be processed
	 *
	 * @return string
	 */
	public function getValue() {
		return $this->tsValue('value');
	}

	/**
	 * Convert URIs matching a supported scheme with generated URIs
	 *
	 * If the workspace of the current node context is not live, no replacement will be done. This is needed to show
	 * the editable links with metadata in the content module.
	 *
	 * @return string
	 * @throws Exception
	 */
	public function evaluate() {
		$text = $this->getValue() ?: '';
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
			switch ($matches[1]) {
				case 'node':
					return $self->convertNodeIdentifierToUri($matches[2], $node);
				case 'asset':
					return $self->convertAssetIdentifierToUri($matches[2], $node);
			}
		}, $text);
	}

	/**
	 * Converts the given node identifier (UUID) with a proper URI pointing to the target node - or with an empty string if the target node was not found in the current workspace
	 *
	 * @param string $nodeIdentifier
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

	/**
	 * Converts the given asset identifier (UUID) with a proper URI pointing to the target asset - or with an empty string if the target asset was not found
	 *
	 * @param string $assetIdentifier
	 * @return string
	 */
	public function convertAssetIdentifierToUri($assetIdentifier) {
		$asset = $this->assetRepository->findByIdentifier($assetIdentifier);
		if ($asset === NULL) {
			return '';
		}

		return $this->resourcePublisher->getPersistentResourceWebUri($asset->getResource());
	}
}
