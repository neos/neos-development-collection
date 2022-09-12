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

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
/** @codingStandardsIgnoreStart */
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
/** @codingStandardsIgnoreEnd */
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeSubject;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use Neos\Flow\Security\Exception\InvalidPrivilegeTypeException;

/**
 * A privilege to restrict node creation
 */
class CreateNodePrivilege extends AbstractNodePrivilege
{
    /**
     * @var CreateNodePrivilegeContext
     */
    protected $nodeContext;

    protected string $nodeContextClassName = CreateNodePrivilegeContext::class;

    /**
     * @param PrivilegeSubjectInterface|CreateNodePrivilegeSubject|MethodPrivilegeSubject $subject
     * @throws InvalidPrivilegeTypeException
     */
    public function matchesSubject(PrivilegeSubjectInterface $subject): bool
    {
        if ($subject instanceof CreateNodePrivilegeSubject === false
            && $subject instanceof MethodPrivilegeSubject === false) {
            throw new InvalidPrivilegeTypeException(sprintf(
                'Privileges of type "%s" only support subjects of type "%s" or "%s",'
                    . ' but we got a subject of type: "%s".',
                CreateNodePrivilege::class,
                CreateNodePrivilegeSubject::class,
                MethodPrivilegeSubject::class,
                get_class($subject)
            ), 1417014353);
        }

        $this->initialize();
        $this->evaluateNodeContext();
        if ($subject instanceof MethodPrivilegeSubject) {
            if ($this->methodPrivilege->matchesSubject($subject) === false) {
                return false;
            }

            $joinPoint = $subject->getJoinPoint();
            $allowedCreationNodeTypes = $this->nodeContext->getCreationNodeTypes();
            $actualNodeType = $joinPoint->getMethodName() === 'createNodeFromTemplate'
                ? $joinPoint->getMethodArgument('nodeTemplate')->getNodeType()->getName()
                : $joinPoint->getMethodArgument('nodeType')->getName();

            if ($allowedCreationNodeTypes !== [] && !in_array($actualNodeType, $allowedCreationNodeTypes)) {
                return false;
            }

            /** @var Node $node */
            $node = $joinPoint->getProxy();
            $nodePrivilegeSubject = new NodePrivilegeSubject($node);
            return parent::matchesSubject($nodePrivilegeSubject);
        }

        $creationNodeType = $subject->getCreationNodeType();
        if ($this->nodeContext->getCreationNodeTypes() === []
            || ($subject->hasCreationNodeType() === false)
            || !is_null($creationNodeType) && in_array(
                $creationNodeType->getName(),
                $this->nodeContext->getCreationNodeTypes()
            ) === true) {
            return parent::matchesSubject($subject);
        }
        return false;
    }

    /**
     * @return array<int,string> $creationNodeTypes
     */
    public function getCreationNodeTypes(): array
    {
        return $this->nodeContext->getCreationNodeTypes();
    }

    protected function buildMethodPrivilegeMatcher(): string
    {
        return 'method(' . CreateNodeVariant::class . '->__construct()) && method('
            . CreateNodeAggregateWithNodeAndSerializedProperties::class . '->__construct())';
    }
}
