<?php
namespace TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\Doctrine;

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
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Authorization\Privilege\Entity\Doctrine\ConditionGenerator as EntityConditionGenerator;
use TYPO3\Flow\Security\Authorization\Privilege\Entity\Doctrine\DisjunctionGenerator;
use TYPO3\Flow\Security\Authorization\Privilege\Entity\Doctrine\PropertyConditionGenerator;
use TYPO3\Flow\Security\Exception\InvalidPrivilegeException;
use TYPO3\Flow\Validation\Validator\UuidValidator;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\ContextFactory;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * A SQL condition generator, supporting special SQL constraints
 * for nodes.
 */
class ConditionGenerator extends EntityConditionGenerator {

	/**
	 * @Flow\Inject
	 * @var ContextFactory
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @var string
	 */
	protected $entityType = 'TYPO3\TYPO3CR\Domain\Model\NodeData';

	/**
	 * @param string $entityType
	 * @return boolean
	 * @throws InvalidPrivilegeException
	 */
	public function isType($entityType) {
		throw new InvalidPrivilegeException('The isType() operator must not be used in Node privilege matchers!', 1417083500);
	}

	/**
	 * @param string $nodePathOrIdentifier
	 * @return PropertyConditionGenerator
	 */
	public function isDescendantNodeOf($nodePathOrIdentifier) {
		if (preg_match(UuidValidator::PATTERN_MATCH_UUID, $nodePathOrIdentifier) === 1) {
			$node = $this->getNodeByIdentifier($nodePathOrIdentifier);
			if ($node === NULL) {
				return NULL;
			}
			$nodePath = $node->getPath();
		} else {
			$nodePath = rtrim($nodePathOrIdentifier, '/');
		}
		$propertyConditionGenerator1 = new PropertyConditionGenerator('path');
		$propertyConditionGenerator2 = new PropertyConditionGenerator('path');

		return new DisjunctionGenerator(array($propertyConditionGenerator1->like($nodePath . '/%'), $propertyConditionGenerator2->equals($nodePath)));
	}

	/**
	 * @param string|array $nodeTypes
	 * @return PropertyConditionGenerator
	 */
	public function nodeIsOfType($nodeTypes) {
		$propertyConditionGenerator = new PropertyConditionGenerator('nodeType');
		if (!is_array($nodeTypes)) {
			$nodeTypes = array($nodeTypes);
		}
		$expandedNodeTypeNames = array();
		foreach ($nodeTypes as $nodeTypeName) {
			$subNodeTypes = $this->nodeTypeManager->getSubNodeTypes($nodeTypeName, FALSE);
			$expandedNodeTypeNames = array_merge($expandedNodeTypeNames, array($nodeTypeName), array_keys($subNodeTypes));
		}
		return $propertyConditionGenerator->in(array_unique($expandedNodeTypeNames));
	}

	/**
	 * @param string|array $workspaceNames
	 * @return PropertyConditionGenerator
	 */
	public function isInWorkspace($workspaceNames) {
		$propertyConditionGenerator = new PropertyConditionGenerator('workspace');
		if (!is_array($workspaceNames)) {
			$workspaceNames = array($workspaceNames);
		}
		return $propertyConditionGenerator->in($workspaceNames);
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