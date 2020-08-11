<?php
namespace Neos\ContentRepository\Migration\Filters;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeData;

/**
 * Filter nodes by node name.
 */
class NodeName implements FilterInterface
{
    /**
     * The node name to match on.
     *
     * @var string
     */
    protected $nodeName;

    /**
     * Sets the node type name to match on.
     *
     * @param string $nodeName
     * @return void
     */
    public function setNodeName($nodeName)
    {
        $this->nodeName = $nodeName;
    }

    /**
     * Returns true if the given node is of the node type this filter expects.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function matches(NodeData $node)
    {
        return $node->getName() === $this->nodeName;
    }
}
