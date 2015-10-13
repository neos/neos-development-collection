<?php
namespace TYPO3\TYPO3CR\Security\Authorization\Privilege\Node;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
