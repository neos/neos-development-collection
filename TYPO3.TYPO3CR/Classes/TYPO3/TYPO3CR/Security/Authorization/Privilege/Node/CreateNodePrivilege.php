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
        if (!($subject instanceof CreateNodePrivilegeSubject) && !($subject instanceof MethodPrivilegeSubject)) {
            throw new InvalidPrivilegeTypeException(
                sprintf(
                    'Privileges of type "%s" only support subjects of type "%s" or "%s", but we got a subject of type: "%s".',
                    CreateNodePrivilege::class,
                    CreateNodePrivilegeSubject::class,
                    MethodPrivilegeSubject::class,
                    \get_class($subject)
                ),
                1417014353
            );
        }

        $this->initialize();
        $allowedCreationNodeTypes = $this->nodeContext->getCreationNodeTypes();
        if ($subject instanceof MethodPrivilegeSubject) {
            if (!$this->methodPrivilege->matchesSubject($subject)) {
                return false;
            }

            $joinPoint = $subject->getJoinPoint();
            $actualNodeType = $joinPoint->getMethodName() === 'createNodeFromTemplate'
                ? $joinPoint->getMethodArgument('nodeTemplate')->getNodeType()->getName()
                : $joinPoint->getMethodArgument('nodeType')->getName()
            ;

            if ($allowedCreationNodeTypes !== [] && !\in_array($actualNodeType, $allowedCreationNodeTypes, true)) {
                return false;
            }

            /** @var NodeInterface $node */
            $node = $joinPoint->getProxy();
            $nodePrivilegeSubject = new NodePrivilegeSubject($node);
            return parent::matchesSubject($nodePrivilegeSubject);
        }

        if ($allowedCreationNodeTypes === [] || !$subject->hasCreationNodeType()) {
            return parent::matchesSubject($subject);
        }

        $creationNodeType = $subject->getCreationNodeType();
        foreach ($allowedCreationNodeTypes as $allowedCreationNodeType) {
            if ($creationNodeType->isOfType($allowedCreationNodeType)) {
                return parent::matchesSubject($subject);
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
