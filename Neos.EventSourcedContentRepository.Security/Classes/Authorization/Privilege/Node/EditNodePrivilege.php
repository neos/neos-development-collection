<?php
namespace Neos\EventSourcedContentRepository\Security\Authorization\Privilege\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\ChangeNodeAggregateName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\ChangeNodeAggregateType;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\DisableNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\EnableNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\MoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\RemoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetNodeReferences;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetSerializedNodeProperties;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeSubject;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use Neos\Flow\Security\Exception\InvalidPrivilegeTypeException;

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

        if ($subject instanceof MethodPrivilegeSubject === true) {
            $this->initializeMethodPrivilege();
            if ($this->methodPrivilege->matchesSubject($subject) === false) {
                return false;
            }

            /** @var NodeBasedReadModelInterface $node */
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
        return  'method(' . SetSerializedNodeProperties::class . '->__construct()) || method(' . SetNodeReferences::class . '->__construct()) || method(' . RemoveNodeAggregate::class . '->__construct()) || method(' . MoveNodeAggregate::class . '->__construct()) || method(' . EnableNodeAggregate::class . '->__construct()) || method(' . DisableNodeAggregate::class . '->__construct()) || method(' . ChangeNodeAggregateName::class . '->__construct()) || method(' . ChangeNodeAggregateType::class . '->__construct())';
    }
}
