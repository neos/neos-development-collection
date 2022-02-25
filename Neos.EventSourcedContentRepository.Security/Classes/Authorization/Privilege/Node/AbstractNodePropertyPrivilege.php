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

use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeSubject;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use Neos\Flow\Security\Exception\InvalidPrivilegeTypeException;

/**
 * Base class for privileges restricting node properties.
 */
abstract class AbstractNodePropertyPrivilege extends AbstractNodePrivilege
{
    /**
     * @var PropertyAwareNodePrivilegeContext
     */
    protected $nodeContext;

    protected string $nodeContextClassName = PropertyAwareNodePrivilegeContext::class;

    /**
     * With this mapping we can treat methods like properties.
     * E.g. we want to be able to have a property "hidden" even though there is no real property
     * called like this. Instead the set/getHidden() methods should match this "property".
     *
     * @var array<string,string>
     */
    protected array $methodNameToPropertyMapping = [];

    /**
     * @param PrivilegeSubjectInterface|PropertyAwareNodePrivilegeSubject|MethodPrivilegeSubject $subject
     * @throws InvalidPrivilegeTypeException
     */
    public function matchesSubject(PrivilegeSubjectInterface $subject): bool
    {
        if (!$subject instanceof PropertyAwareNodePrivilegeSubject && !$subject instanceof MethodPrivilegeSubject) {
            throw new InvalidPrivilegeTypeException(sprintf(
                'Privileges of type "%s" only support subjects of type "%s" or "%s",'
                    . ' but we got a subject of type: "%s".',
                ReadNodePropertyPrivilege::class,
                PropertyAwareNodePrivilegeSubject::class,
                MethodPrivilegeSubject::class,
                get_class($subject)
            ), 1417018448);
        }

        $this->initialize();
        $this->evaluateNodeContext();
        if ($subject instanceof MethodPrivilegeSubject) {
            if ($this->methodPrivilege->matchesSubject($subject) === false) {
                return false;
            }

            $joinPoint = $subject->getJoinPoint();

            // if the context isn't restricted to certain properties, it matches *all* properties
            if ($this->nodeContext->hasProperties()) {
                $methodName = $joinPoint->getMethodName();
                $propertyName = $this->methodNameToPropertyMapping[$methodName]
                    ?? $joinPoint->getMethodArgument('propertyName');
                if (!in_array($propertyName, $this->nodeContext->getNodePropertyNames())) {
                    return false;
                }
            }

            /** @var NodeInterface $node */
            $node = $joinPoint->getProxy();
            $nodePrivilegeSubject = new NodePrivilegeSubject($node);
            return parent::matchesSubject($nodePrivilegeSubject);
        }
        if ($subject->hasPropertyName() && !in_array(
            $subject->getPropertyName(),
            $this->nodeContext->getNodePropertyNames()
        )) {
            return false;
        }
        return parent::matchesSubject($subject);
    }

    /**
     * @return array<int,string>
     */
    public function getNodePropertyNames(): array
    {
        return $this->nodeContext->getNodePropertyNames();
    }
}
