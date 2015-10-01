<?php
namespace TYPO3\TYPO3CR\Security\Authorization\Privilege\Node;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A node privilege subject
 */
class NodePrivilegeSubject implements PrivilegeSubjectInterface
{
    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * @var JoinPointInterface
     */
    protected $joinPoint;

    /**
     * @param NodeInterface $node The node we will check privileges for
     * @param JoinPointInterface $joinPoint If we intercept node operations, this joinpoint represents the method called on the node and holds a reference to the node we will check privileges for
     */
    public function __construct(NodeInterface $node, JoinPointInterface $joinPoint = null)
    {
        $this->node = $node;
        $this->joinPoint = $joinPoint;
    }

    /**
     * @return NodeInterface
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @return JoinPointInterface
     */
    public function getJoinPoint()
    {
        return $this->joinPoint;
    }
}
