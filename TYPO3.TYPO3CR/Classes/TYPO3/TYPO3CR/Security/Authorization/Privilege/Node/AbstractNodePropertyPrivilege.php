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
 * Base class for privileges restricting node properties.
 */
abstract class AbstractNodePropertyPrivilege extends AbstractNodePrivilege {

	/**
	 * @var PropertyAwareNodePrivilegeContext
	 */
	protected $nodeContext;

	/**
	 * @var string
	 */
	protected $nodeContextClassName = PropertyAwareNodePrivilegeContext::class;

	/**
	 * With this mapping we can treat methods like properties. E.g. we want to be able to have a property "hidden" even though there is no real property
	 * called like this. Instead the set/getHidden() methods should match this "property".
	 *
	 * @var array
	 */
	protected $methodNameToPropertyMapping = array();

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

			// if the context isn't restricted to certain properties, it matches *all* properties
			if ($this->nodeContext->hasProperties()) {
				$methodName = $joinPoint->getMethodName();
				$actualPropertyName = NULL;

				if (isset($this->methodNameToPropertyMapping[$methodName])) {
					$propertyName = $this->methodNameToPropertyMapping[$methodName];
				} else {
					$propertyName = $joinPoint->getMethodArgument('propertyName');
				}
				if (!in_array($propertyName, $this->nodeContext->getNodePropertyNames())) {
					return FALSE;
				}
			}

			/** @var NodeInterface $node */
			$node = $joinPoint->getProxy();
			$nodePrivilegeSubject = new NodePrivilegeSubject($node);
			return parent::matchesSubject($nodePrivilegeSubject);
		}
		if ($subject->hasPropertyName() && in_array($subject->getPropertyName(), $this->nodeContext->getNodePropertyNames()) === FALSE) {
			return FALSE;
		}
		return parent::matchesSubject($subject);
	}

	/**
	 * @return array
	 */
	public function getNodePropertyNames() {
		return $this->nodeContext->getNodePropertyNames();
	}
}