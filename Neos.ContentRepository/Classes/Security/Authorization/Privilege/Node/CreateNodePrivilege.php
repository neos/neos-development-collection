<?php
namespace Neos\ContentRepository\Security\Authorization\Privilege\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeSubject;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use Neos\Flow\Security\Exception\InvalidPrivilegeTypeException;
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * A privilege to restrict node creation
 */
class CreateNodePrivilege extends AbstractNodePrivilege
{
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
    public function matchesSubject(PrivilegeSubjectInterface $subject)
    {
        if ($subject instanceof CreateNodePrivilegeSubject === false && $subject instanceof MethodPrivilegeSubject === false) {
            throw new InvalidPrivilegeTypeException(sprintf('Privileges of type "%s" only support subjects of type "%s" or "%s", but we got a subject of type: "%s".', CreateNodePrivilege::class, CreateNodePrivilegeSubject::class, MethodPrivilegeSubject::class, get_class($subject)), 1417014353);
        }

        $this->initialize();
        if ($subject instanceof MethodPrivilegeSubject) {
            if ($this->methodPrivilege->matchesSubject($subject) === false) {
                return false;
            }

            $joinPoint = $subject->getJoinPoint();
            $allowedCreationNodeTypes = $this->nodeContext->getCreationNodeTypes();
            $actualNodeType = $joinPoint->getMethodName() === 'createNodeFromTemplate' ? $joinPoint->getMethodArgument('nodeTemplate')->getNodeType()->getName() : $joinPoint->getMethodArgument('nodeType')->getName();

            if ($allowedCreationNodeTypes !== array() && !in_array($actualNodeType, $allowedCreationNodeTypes)) {
                return false;
            }

            /** @var NodeInterface $node */
            $node = $joinPoint->getProxy();
            $nodePrivilegeSubject = new NodePrivilegeSubject($node);
            $result = parent::matchesSubject($nodePrivilegeSubject);
            return $result;
        }

        if ($this->nodeContext->getCreationNodeTypes() === array() || ($subject->hasCreationNodeType() === false) || in_array($subject->getCreationNodeType()->getName(), $this->nodeContext->getCreationNodeTypes()) === true) {
            return parent::matchesSubject($subject);
        }
        return false;
    }

    /**
     * @return array $creationNodeTypes
     */
    public function getCreationNodeTypes()
    {
        return $this->nodeContext->getCreationNodeTypes();
    }

    /**
     * @return string
     */
    protected function buildMethodPrivilegeMatcher()
    {
        return 'within(' . NodeInterface::class . ') && method(.*->(createNode|createNodeFromTemplate)())';
    }
}
