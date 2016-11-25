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
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;

/**
 * A create node privilege subject
 */
class CreateNodePrivilegeSubject extends NodePrivilegeSubject
{
    /**
     * @var NodeType
     */
    protected $creationNodeType;

    /**
     * @param NodeInterface $node The parent node under which a new child shall be created
     * @param NodeType $creationNodeType The node type of the new child node, to check if this is type is allowed as new child node under the given parent node
     * @param JoinPointInterface $joinPoint Set, if created by a method interception. Usually the interception of the createNode() method, where the creation of new child nodes takes place
     */
    public function __construct(NodeInterface $node, NodeType $creationNodeType = null, JoinPointInterface $joinPoint = null)
    {
        $this->creationNodeType = $creationNodeType;
        parent::__construct($node, $joinPoint);
    }

    /**
     * @return boolean
     */
    public function hasCreationNodeType()
    {
        return ($this->creationNodeType !== null);
    }

    /**
     * @return NodeType
     */
    public function getCreationNodeType()
    {
        return $this->creationNodeType;
    }
}
