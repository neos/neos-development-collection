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

use Neos\ContentRepository\Domain\Model\NodeType;
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
        $this->evaluateNodeContext();
        if ($subject instanceof MethodPrivilegeSubject) {
            if ($this->methodPrivilege->matchesSubject($subject) === false) {
                return false;
            }
            $joinPoint = $subject->getJoinPoint();
            /** @var NodeType $actualNodeType */
            $actualNodeType = $joinPoint->getMethodName() === 'createNodeFromTemplate' ? $joinPoint->getMethodArgument('nodeTemplate')->getNodeType() : $joinPoint->getMethodArgument('nodeType');
            if (!$this->matchesNodeType($actualNodeType)) {
                return false;
            }

            /** @var NodeInterface $node */
            $node = $joinPoint->getProxy();
            $nodePrivilegeSubject = new NodePrivilegeSubject($node);
            return parent::matchesSubject($nodePrivilegeSubject);
        }

        if (!$subject->hasCreationNodeType() || $this->matchesNodeType($subject->getCreationNodeType())) {
            return parent::matchesSubject($subject);
        }
        return false;
    }

    /**
     * Whether this privilege matches the specified node type (including super types)
     *
     * @param NodeType $nodeType
     * @return bool
     */
    private function matchesNodeType(NodeType $nodeType): bool
    {
        // no "createdNodeIsOfType" constraint => this privilege matches all node types
        if ($this->nodeContext->getCreationNodeTypes() === []) {
            return true;
        }
        foreach ($this->nodeContext->getCreationNodeTypes() as $nodeTypeName) {
            if ($nodeType->isOfType($nodeTypeName)) {
                return true;
            }
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
