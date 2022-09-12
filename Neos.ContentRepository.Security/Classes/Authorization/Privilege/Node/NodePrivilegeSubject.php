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
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;

/**
 * A node privilege subject
 */
class NodePrivilegeSubject implements PrivilegeSubjectInterface
{
    protected Node $node;

    protected ?JoinPointInterface $joinPoint;

    /**
     * @param Node $node The node we will check privileges for
     * @param ?JoinPointInterface $joinPoint If we intercept node operations,
     * this joinpoint represents the method called on the node and holds a reference to the node
     * we will check privileges for
     */
    public function __construct(Node $node, ?JoinPointInterface $joinPoint = null)
    {
        $this->node = $node;
        $this->joinPoint = $joinPoint;
    }

    public function getNode(): Node
    {
        return $this->node;
    }

    public function getJoinPoint(): ?JoinPointInterface
    {
        return $this->joinPoint;
    }
}
