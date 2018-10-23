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

use Neos\Eel\CompilingEvaluator;
use Neos\Eel\Context;
use Neos\Flow\Aop\Pointcut\PointcutFilterInterface;
use Neos\Flow\Security\Authorization\Privilege\AbstractPrivilege;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilege;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeSubject;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeTarget;
use Neos\Flow\Security\Exception\InvalidPrivilegeTypeException;

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
     * Constructor
     *
     * @param PrivilegeTarget $privilegeTarget
     * @param string $matcher
     * @param string $permission
     * @param $parameters
     */
    public function __construct(PrivilegeTarget $privilegeTarget, string $matcher, string $permission, $parameters)
    {
        parent::__construct($privilegeTarget, $matcher, $permission, $parameters);
        $this->cacheEntryIdentifier = null;
    }

    /**
     * @return void
     */
    public function initialize()
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;
        $this->eelCompilingEvaluator = $this->objectManager->get(CompilingEvaluator::class);
        $this->nodeContext = new $this->nodeContextClassName();
        $this->initializeMethodPrivilege();
    }

    /**
     * @return void
     */
    protected function buildCacheEntryIdentifier()
    {
        $this->cacheEntryIdentifier = md5($this->privilegeTarget->getIdentifier() . '__methodPrivilege' . '|' . $this->buildMethodPrivilegeMatcher());
    }

    /**
     * Unique identifier of this privilege
     *
     * @return string
     */
    public function getCacheEntryIdentifier(): string
    {
        if ($this->cacheEntryIdentifier === null) {
            $this->buildCacheEntryIdentifier();
        }

        return $this->cacheEntryIdentifier;
    }

    /**
     * @param PrivilegeSubjectInterface|NodePrivilegeSubject|MethodPrivilegeSubject $subject (one of NodePrivilegeSubject or MethodPrivilegeSubject)
     * @return boolean
     * @throws InvalidPrivilegeTypeException
     */
    public function matchesSubject(PrivilegeSubjectInterface $subject)
    {
        if ($subject instanceof NodePrivilegeSubject === false && $subject instanceof MethodPrivilegeSubject === false) {
            throw new InvalidPrivilegeTypeException(sprintf('Privileges of type "%s" only support subjects of type "%s" or "%s", but we got a subject of type: "%s".', AbstractNodePrivilege::class, NodePrivilegeSubject::class, MethodPrivilegeSubject::class, get_class($subject)), 1417014368);
        }

        if ($subject instanceof MethodPrivilegeSubject) {
            $this->initializeMethodPrivilege();
            return $this->methodPrivilege->matchesSubject($subject);
        }

        $this->initialize();
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
        $this->initializeMethodPrivilege();
        return $this->methodPrivilege->matchesMethod($className, $methodName);
    }

    /**
     * @return PointcutFilterInterface
     */
    public function getPointcutFilterComposite()
    {
        $this->initializeMethodPrivilege();
        return $this->methodPrivilege->getPointcutFilterComposite();
    }

    /**
     * @throws \Neos\Flow\Security\Exception
     */
    protected function initializeMethodPrivilege()
    {
        if ($this->methodPrivilege !== null) {
            return;
        }
        $methodPrivilegeMatcher = $this->buildMethodPrivilegeMatcher();
        $methodPrivilegeTarget = new PrivilegeTarget($this->privilegeTarget->getIdentifier() . '__methodPrivilege', MethodPrivilege::class, $methodPrivilegeMatcher);
        $methodPrivilegeTarget->injectObjectManager($this->objectManager);
        $this->methodPrivilege = $methodPrivilegeTarget->createPrivilege($this->getPermission(), $this->getParameters());
    }

    /**
     * Evaluates the matcher with this objects nodeContext and returns the result.
     *
     * @return mixed
     */
    protected function evaluateNodeContext()
    {
        $eelContext = new Context($this->nodeContext);
        return $this->eelCompilingEvaluator->evaluate($this->getParsedMatcher(), $eelContext);
    }

    /**
     * @return string
     */
    abstract protected function buildMethodPrivilegeMatcher();
}
