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

use TYPO3\Eel\CompilingEvaluator;
use TYPO3\Eel\Context;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\Pointcut\PointcutFilterInterface;
use TYPO3\Flow\Security\Authorization\Privilege\AbstractPrivilege;
use TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface;
use TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeSubject;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeTarget;
use TYPO3\Flow\Security\Exception\InvalidPrivilegeTypeException;

/**
 * An abstract node privilege acting as a base class for other
 * node privileges restricting operations and data on nodes.
 */
abstract class AbstractNodePrivilege extends AbstractPrivilege implements MethodPrivilegeInterface
{
    /**
     * @var CompilingEvaluator
     */
    protected $eelCompilingEvaluator;

    /**
     * @var string
     */
    protected $nodeContextClassName = NodePrivilegeContext::class;

    /**
     * @var NodePrivilegeContext
     */
    protected $nodeContext;

    /**
     * @var MethodPrivilegeInterface
     */
    protected $methodPrivilege;

    /**
     * @var boolean
     */
    protected $initialized = false;

    /**
     * @return void
     */
    public function initialize()
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;

        $this->nodeContext = new $this->nodeContextClassName();
        $eelContext = new Context($this->nodeContext);

        $this->eelCompilingEvaluator = new CompilingEvaluator();

        $this->eelCompilingEvaluator->evaluate($this->getParsedMatcher(), $eelContext);

        $methodPrivilegeMatcher = $this->buildMethodPrivilegeMatcher();

        $methodPrivilegeTarget = new PrivilegeTarget($this->privilegeTarget->getIdentifier() . '__methodPrivilege', '\TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilege', $methodPrivilegeMatcher);
        $methodPrivilegeTarget->injectObjectManager($this->objectManager);
        $this->methodPrivilege = $methodPrivilegeTarget->createPrivilege($this->getPermission(), $this->getParameters());
    }

    /**
     * Unique identifier of this privilege
     *
     * @return string
     */
    public function getCacheEntryIdentifier()
    {
        $this->initialize();
        return $this->methodPrivilege->getCacheEntryIdentifier();
    }

    /**
     * @param PrivilegeSubjectInterface|NodePrivilegeSubject|MethodPrivilegeSubject $subject (one of NodePrivilegeSubject or MethodPrivilegeSubject)
     * @return boolean
     * @throws InvalidPrivilegeTypeException
     */
    public function matchesSubject(PrivilegeSubjectInterface $subject)
    {
        if ($subject instanceof NodePrivilegeSubject === false && $subject instanceof MethodPrivilegeSubject === false) {
            throw new InvalidPrivilegeTypeException(sprintf('Privileges of type "TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\AbstractNodePrivilege" only support subjects of type "TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\NodePrivilegeSubject" or "TYPO3\Flow\Security\Method\MethodPrivilegeSubject", but we got a subject of type: "%s".', get_class($subject)), 1417014368);
        }

        $this->initialize();

        if ($subject instanceof MethodPrivilegeSubject) {
            return $this->methodPrivilege->matchesSubject($subject);
        }

        $nodeContext = new $this->nodeContextClassName($subject->getNode());
        $eelContext = new Context($nodeContext);

        return $this->eelCompilingEvaluator->evaluate($this->getParsedMatcher(), $eelContext);
    }

    /**
     * @param string $className
     * @param string $methodName
     * @return boolean
     */
    public function matchesMethod($className, $methodName)
    {
        $this->initialize();
        return $this->methodPrivilege->matchesMethod($className, $methodName);
    }

    /**
     * @return PointcutFilterInterface
     */
    public function getPointcutFilterComposite()
    {
        $this->initialize();
        return $this->methodPrivilege->getPointcutFilterComposite();
    }

    /**
     * @return string
     */
    abstract protected function buildMethodPrivilegeMatcher();
}
