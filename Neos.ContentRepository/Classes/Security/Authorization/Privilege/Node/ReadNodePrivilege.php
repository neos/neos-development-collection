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
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\EntityPrivilege;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use Neos\Flow\Security\Exception\InvalidPrivilegeTypeException;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Security\Authorization\Privilege\Node\Doctrine\ConditionGenerator;

/**
 * A node privilege to restricting reading of nodes.
 * Nodes not granted for reading will be filtered via SQL.
 *
 * Currently only doctrine persistence is supported as we use
 * the doctrine filter api, to rewrite SQL queries.
 */
class ReadNodePrivilege extends EntityPrivilege
{
    /**
     * @param string $entityType
     * @return boolean
     */
    public function matchesEntityType($entityType)
    {
        return $entityType === NodeData::class;
    }

    /**
     * @return ConditionGenerator
     */
    protected function getConditionGenerator()
    {
        return new ConditionGenerator();
    }

    /**
     * @param PrivilegeSubjectInterface $subject
     * @return boolean
     * @throws InvalidPrivilegeTypeException
     */
    public function matchesSubject(PrivilegeSubjectInterface $subject)
    {
        if (!$subject instanceof NodePrivilegeSubject) {
            throw new InvalidPrivilegeTypeException(sprintf('Privileges of type "%s" only support subjects of type "%s", but we got a subject of type: "%s".', static::class, NodePrivilegeSubject::class, get_class($subject)), 1465979693);
        }
        $nodeContext = new NodePrivilegeContext($subject->getNode());
        $eelContext = new Context($nodeContext);
        $eelCompilingEvaluator = $this->objectManager->get(CompilingEvaluator::class);
        return $eelCompilingEvaluator->evaluate($this->getParsedMatcher(), $eelContext);
    }
}
