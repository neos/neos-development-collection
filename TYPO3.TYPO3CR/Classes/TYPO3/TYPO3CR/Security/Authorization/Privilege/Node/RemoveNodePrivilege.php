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
 * A privilege to remove nodes
 */
class RemoveNodePrivilege extends AbstractNodePrivilege
{
    /**
     * @param PrivilegeSubjectInterface|NodePrivilegeSubject|MethodPrivilegeSubject $subject
     * @return boolean
     * @throws InvalidPrivilegeTypeException
     */
    public function matchesSubject(PrivilegeSubjectInterface $subject)
    {
        if ($subject instanceof NodePrivilegeSubject === false && $subject instanceof MethodPrivilegeSubject === false) {
            throw new InvalidPrivilegeTypeException(sprintf('Privileges of type "TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\EditNodePrivilege" only support subjects of type "TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\NodePrivilegeSubject" or "TYPO3\Flow\Security\Method\MethodPrivilegeSubject", but we got a subject of type: "%s".', get_class($subject)), 1417017296);
        }

        $this->initialize();
        if ($subject instanceof MethodPrivilegeSubject) {
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
     * @return string
     */
    protected function buildMethodPrivilegeMatcher()
    {
        return 'within(TYPO3\TYPO3CR\Domain\Model\NodeInterface) && method(.*->(remove|setRemoved)())';
    }
}
