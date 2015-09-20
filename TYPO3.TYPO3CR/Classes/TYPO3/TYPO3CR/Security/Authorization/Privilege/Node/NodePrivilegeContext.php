<?php
namespace TYPO3\TYPO3CR\Security\Authorization\Privilege\Node;

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
use TYPO3\Flow\Security\Context as SecurityContext;
use TYPO3\Flow\Validation\Validator\UuidValidator;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\ContentDimensionPresetSourceInterface;
use TYPO3\TYPO3CR\Domain\Service\ContextFactory;

/**
 * An Eel context matching expression for the node privileges
 */
class NodePrivilegeContext {

	/**
	 * @Flow\Inject
	 * @var ContextFactory
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var SecurityContext
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var ContentDimensionPresetSourceInterface
	 */
	protected $contentDimensionPresetSource;

	/**
	 * @var NodeInterface
	 */
	protected $node;

	/**
	 * @param NodeInterface $node
	 */
	function __construct(NodeInterface $node = NULL) {
		$this->node = $node;
	}

	/**
	 * @param NodeInterface $node
	 * @return void
	 */
	public function setNode(NodeInterface $node) {
		$this->node = $node;
	}

	/**
	 * @param string $nodePathOrIdentifier
	 * @return boolean
	 */
	public function isDescendantNodeOf($nodePathOrIdentifier) {
		if ($this->node === NULL) {
			return TRUE;
		}
		if (preg_match(UuidValidator::PATTERN_MATCH_UUID, $nodePathOrIdentifier) === 1) {
			if ($this->node->getIdentifier() === $nodePathOrIdentifier) {
				return TRUE;
			}
			$node = $this->getNodeByIdentifier($nodePathOrIdentifier);
			if ($node === NULL) {
				return FALSE;
			}
			$nodePath = $node->getPath() . '/';
		} else {
			$nodePath = rtrim($nodePathOrIdentifier, '/') . '/';
		}
		return substr($this->node->getPath() . '/', 0, strlen($nodePath)) === $nodePath;
	}

	/**
	 * @param string $nodePathOrIdentifier
	 * @return boolean
	 */
	public function isParentNodeOf($nodePathOrIdentifier) {
		if ($this->node === NULL) {
			return TRUE;
		}
		if (preg_match(UuidValidator::PATTERN_MATCH_UUID, $nodePathOrIdentifier) === 1) {
			if ($this->node->getIdentifier() === $nodePathOrIdentifier) {
				return TRUE;
			}
			$node = $this->getNodeByIdentifier($nodePathOrIdentifier);
			if ($node === NULL) {
				return FALSE;
			}
			$nodePath = $node->getPath() . '/';
		} else {
			$nodePath = rtrim($nodePathOrIdentifier, '/') . '/';
		}
		return substr($nodePath . '/', 0, strlen($this->node->getPath())) === $this->node->getPath();
	}

	/**
	 * @param string|array $nodeTypes
	 * @return boolean
	 */
	public function nodeIsOfType($nodeTypes) {
		if ($this->node === NULL) {
			return TRUE;
		}
		if (!is_array($nodeTypes)) {
			$nodeTypes = array($nodeTypes);
		}

		foreach ($nodeTypes as $nodeType) {
			if ($this->node->getNodeType()->isOfType($nodeType)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * @param string|array $workspaceNames
	 * @return boolean
	 */
	public function isInWorkspace($workspaceNames) {
		if ($this->node === NULL) {
			return TRUE;
		}

		return in_array($this->node->getWorkspace()->getName(), $workspaceNames);
	}

	/**
	 * Matches if the currently-selected preset in the passed $dimensionName is one of $presets.
	 *
	 * Example: isInDimensionPreset('language', 'de') checks whether the currently-selected language
	 * preset (in the Neos backend) is "de".
	 *
	 * Implementation Note: We deliberately work on the Dimension Preset Name, and not on the
	 * dimension values itself; as the preset is user-visible and the actual dimension-values
	 * for a preset are just implementation details.
	 *
	 * @param string $dimensionName
	 * @param string|array $presets
	 * @return boolean
	 */
	public function isInDimensionPreset($dimensionName, $presets) {
		if ($this->node === NULL) {
			return TRUE;
		}

		$dimensionValues = $this->node->getContext()->getDimensions();
		if (!isset($dimensionValues[$dimensionName])) {
			return FALSE;
		}

		$preset = $this->contentDimensionPresetSource->findPresetByDimensionValues($dimensionName, $dimensionValues[$dimensionName]);

		if ($preset === NULL) {
			return FALSE;
		}
		$presetIdentifier = $preset['identifier'];

		if (!is_array($presets)) {
			$presets = array($presets);
		}

		return in_array($presetIdentifier, $presets);
	}

	/**
	 * @param string $nodeIdentifier
	 * @return NodeInterface
	 */
	protected function getNodeByIdentifier($nodeIdentifier) {
		$context = $this->contextFactory->create();
		$node = NULL;
		$this->securityContext->withoutAuthorizationChecks(function() use ($nodeIdentifier, $context, &$node) {
			$node = $context->getNodeByIdentifier($nodeIdentifier);
		});
		$context->getFirstLevelNodeCache()->setByIdentifier($nodeIdentifier, NULL);
		return $node;
	}
}