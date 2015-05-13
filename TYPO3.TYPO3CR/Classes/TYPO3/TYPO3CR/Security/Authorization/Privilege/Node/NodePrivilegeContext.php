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