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

use TYPO3\Eel\Context;
use TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeSubject;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use TYPO3\Flow\Security\Exception\InvalidPrivilegeTypeException;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A privilege to restrict node creation
 */
class CreateNodePrivilege extends AbstractNodePrivilege {

	/**
	 * @var CreateNodePrivilegeContext
	 */
	protected $nodeContext;

	/**
	 * @var string
	 */
	protected $nodeContextClassName = CreateNodePrivilegeContext::class;

	/**
	 * @param PrivilegeSubjectInterface|CreateNodePrivilegeSubject|MethodPrivilegeSubject $subject
	 * @return boolean
	 * @throws InvalidPrivilegeTypeException
	 */
	public function matchesSubject(PrivilegeSubjectInterface $subject) {
		if ($subject instanceof CreateNodePrivilegeSubject === FALSE && $subject instanceof MethodPrivilegeSubject === FALSE) {
			throw new InvalidPrivilegeTypeException(sprintf('Privileges of type "TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\CreateNodePrivilege" only support subjects of type "TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\CreateNodePrivilegeSubject" or "TYPO3\Flow\Security\Method\MethodPrivilegeSubject", but we got a subject of type: "%s".', get_class($subject)), 1417014353);
		}

		$this->initialize();
		if ($subject instanceof MethodPrivilegeSubject) {
			if ($this->methodPrivilege->matchesSubject($subject) === FALSE) {
				return FALSE;
			}

			$joinPoint = $subject->getJoinPoint();
			$allowedCreationNodeTypes = $this->nodeContext->getCreationNodeTypes();
			$actualNodeType = $joinPoint->getMethodName() === 'createNodeFromTemplate' ? $joinPoint->getMethodArgument('nodeTemplate')->getNodeType()->getName() : $joinPoint->getMethodArgument('nodeType')->getName();

			if ($allowedCreationNodeTypes !== array() && !in_array($actualNodeType, $allowedCreationNodeTypes)) {
				return FALSE;
			}

			$nodePrivilegeSubject = new NodePrivilegeSubject($joinPoint->getProxy());
			$result = parent::matchesSubject($nodePrivilegeSubject);
			return $result;
		}

		if ($this->nodeContext->getCreationNodeTypes() === array() || ($subject->hasCreationNodeType() === FALSE) || in_array($subject->getCreationNodeType()->getName(), $this->nodeContext->getCreationNodeTypes()) === TRUE) {
			return parent::matchesSubject($subject);
		}
		return FALSE;
	}

	/**
	 * @return array $creationNodeTypes
	 */
	public function getCreationNodeTypes() {
		return $this->nodeContext->getCreationNodeTypes();
	}

	/**
	 * @return string
	 */
	protected function buildMethodPrivilegeMatcher() {
		return 'within(TYPO3\TYPO3CR\Domain\Model\NodeInterface) && method(.*->(createNode|createNodeFromTemplate)())';
	}
}