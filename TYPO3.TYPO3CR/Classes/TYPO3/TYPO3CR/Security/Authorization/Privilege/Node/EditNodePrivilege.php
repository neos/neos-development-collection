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
 * A privilege to restrict editing of nodes and their properties
 */
class EditNodePrivilege extends AbstractNodePrivilege
{
    /**
     * @param PrivilegeSubjectInterface|NodePrivilegeSubject|MethodPrivilegeSubject $subject
     * @return boolean
     * @throws InvalidPrivilegeTypeException
     */
    public function matchesSubject(PrivilegeSubjectInterface $subject)
    {
        if (!$subject instanceof NodePrivilegeSubject && !$subject instanceof MethodPrivilegeSubject) {
            throw new InvalidPrivilegeTypeException(sprintf('Privileges of type "%s" only support subjects of type "%s" or "%s", but we got a subject of type: "%s".', EditNodePrivilege::class, NodePrivilegeSubject::class, MethodPrivilegeSubject::class, get_class($subject)), 1417017239);
        }

        $this->initialize();
        if ($subject instanceof MethodPrivilegeSubject === true) {
            if ($this->methodPrivilege->matchesSubject($subject) === false) {
                return false;
            }

            /** @var NodeInterface $node */
            $node = $subject->getJoinPoint()->getProxy();
            $nodePrivilegeSubject = new NodePrivilegeSubject($node);
            return parent::matchesSubject($nodePrivilegeSubject);
        }

        return parent::matchesSubject($subject);
    }

    /**
     * This is the pointcut expression for all methods to intercept. It targets all public methods that could change the outer state of a node.
     * Note: NodeInterface::setIndex() is excluded because that might be called by the system when redistributing nodes on one level
     *
     * @return string
     */
    protected function buildMethodPrivilegeMatcher()
    {
        return 'within(TYPO3\TYPO3CR\Domain\Model\NodeInterface) && method(public .*->(?!setIndex)(set|unset|remove)[A-Z].*())';
    }
}
