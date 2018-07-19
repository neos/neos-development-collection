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

use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use Neos\Neos\Domain\Context\Content\NodeAddress;

/**
 * A node privilege subject
 */
class NodePrivilegeSubject implements PrivilegeSubjectInterface
{
    /**
     * @var NodeAddress
     */
    protected $nodeAddress;

    /**
     * @var JoinPointInterface
     */
    protected $joinPoint;

    /**
     * @param NodeAddress $nodeAddress The node we will check privileges for
     * @param JoinPointInterface $joinPoint If we intercept node operations, this joinpoint represents the method called on the node and holds a reference to the node we will check privileges for
     */
    public function __construct(NodeAddress $nodeAddress, JoinPointInterface $joinPoint = null)
    {
        $this->nodeAddress = $nodeAddress;
        $this->joinPoint = $joinPoint;
    }

    /**
     * @return NodeAddress
     */
    public function getNodeAddress(): NodeAddress
    {
        return $this->nodeAddress;
    }

    /**
     * @return JoinPointInterface
     */
    public function getJoinPoint()
    {
        return $this->joinPoint;
    }
}
