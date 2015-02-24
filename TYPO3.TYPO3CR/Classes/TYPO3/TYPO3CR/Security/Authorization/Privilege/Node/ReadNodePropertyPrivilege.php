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

use TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeSubject;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use TYPO3\Flow\Security\Exception\InvalidPrivilegeTypeException;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A privilege to restrict reading of node properties.
 *
 * This is needed, as the technical implementation is not based on the entity privilege type, that
 * the read node privilege (retrieving the node at all) ist based on.
 */
class ReadNodePropertyPrivilege extends AbstractNodePrivilege {

	/**
	 * @var PropertyAwareNodePrivilegeContext
	 */
	protected $nodeContext;

	/**
	 * @var string
	 */
	protected $nodeContextClassName = PropertyAwareNodePrivilegeContext::class;

	/**
	 * @param PrivilegeSubjectInterface|PropertyAwareNodePrivilegeSubject|MethodPrivilegeSubject $subject
	 * @return boolean
	 * @throws InvalidPrivilegeTypeException
	 */
	public function matchesSubject(PrivilegeSubjectInterface $subject) {
		if ($subject instanceof PropertyAwareNodePrivilegeSubject === FALSE && $subject instanceof MethodPrivilegeSubject === FALSE) {
			throw new InvalidPrivilegeTypeException(sprintf('Privileges of type "TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\ReadNodePropertyPrivilege" only support subjects of type "TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\PropertyAwareNodePrivilegeSubject" or "TYPO3\Flow\Security\Method\MethodPrivilegeSubject", but we got a subject of type: "%s".', get_class($subject)), 1417018448);
		}

		$this->initialize();
		if ($subject instanceof MethodPrivilegeSubject) {
			if ($this->methodPrivilege->matchesSubject($subject) === FALSE) {
				return FALSE;
			}

			$joinPoint = $subject->getJoinPoint();
			$nodePropertyNames = $this->nodeContext->getNodePropertyNames();
			if (count($nodePropertyNames) > 0) {
				$methodNameAttributeMapping = array(
					'getName' => 'name',
					'isHidden' => 'hidden',
					'getHiddenBeforeDateTime' => 'hiddenBeforeDateTime',
					'getHiddenAfterDateTime' => 'hiddenAfterDateTime',
					'getAccessRoles' => 'accessRoles',
				);

				$methodName = $joinPoint->getMethodName();
				if ((isset($methodNameAttributeMapping[$methodName]) && in_array($methodNameAttributeMapping[$methodName], $nodePropertyNames) === FALSE) || ($joinPoint->getMethodName() === 'getProperty' && in_array($joinPoint->getMethodArgument('propertyName'), $nodePropertyNames) === FALSE)) {
					return FALSE;
				}
			}
			/** @var NodeInterface $node */
			$node = $subject->getJoinPoint()->getProxy();
			$nodePrivilegeSubject = new NodePrivilegeSubject($node);
			return parent::matchesSubject($nodePrivilegeSubject);
		}
		if (in_array($subject->getPropertyName(), $this->nodeContext->getNodePropertyNames()) === FALSE) {
			return FALSE;
		}
		return parent::matchesSubject($subject);
	}

	/**
	 * @return string
	 */
	protected function buildMethodPrivilegeMatcher() {
		return 'within(TYPO3\TYPO3CR\Domain\Model\NodeInterface) && method(.*->(getProperty|getName|isHidden|getHiddenBeforeDateTime|getHiddenAfterDateTime|isHiddenInIndex|getAccessRoles)())';
	}
}