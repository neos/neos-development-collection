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

use Neos\Flow\Annotations as Flow;
use Neos\Utility\ObjectAccess;
use Neos\ContentRepository\Domain\Model\NodeData;

/**
 * Filter nodes by node type.
 */
class NodeType implements FilterInterface
{
    /**
     * The node type to match on.
     *
     * @var string
     */
    protected $nodeTypeName;

    /**
     * If set to true also all subtypes of the given nodeType will match.
     *
     * @var boolean
     */
    protected $withSubTypes = false;

    /**
     * If set this NodeType is actually excluded instead exclusively included.
     *
     * @var boolean
     */
    protected $exclude = false;

    /**
     * Sets the node type name to match on.
     *
     * @param string $nodeTypeName
     * @return void
     */
    public function setNodeType($nodeTypeName)
    {
        $this->nodeTypeName = $nodeTypeName;
    }

    /**
     * Whether the filter should match also on all subtypes of the configured
     * node type.
     *
     * Note: This can only be used with node types still available in the
     * system!
     *
     * @param boolean $withSubTypes
     * @return void
     */
    public function setWithSubTypes($withSubTypes)
    {
        $this->withSubTypes = $withSubTypes;
    }

    /**
     * Whether the filter should exclude the given NodeType instead of including only this node type.
     *
     * @param boolean $exclude
     */
    public function setExclude($exclude)
    {
        $this->exclude = $exclude;
    }

    /**
     * Returns TRUE if the given node is of the node type this filter expects.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function matches(NodeData $node)
    {
        if ($this->withSubTypes === true) {
            $nodeIsMatchingNodeType = $node->getNodeType()->isOfType($this->nodeTypeName);
        } else {
            // This is needed to get the raw string NodeType to prevent errors for NodeTypes that no longer exist.
            $nodeType = ObjectAccess::getProperty($node, 'nodeType', true);
            $nodeIsMatchingNodeType = $nodeType === $this->nodeTypeName;
        }

        if ($this->exclude === true) {
            return !$nodeIsMatchingNodeType;
        }

        return $nodeIsMatchingNodeType;
    }
}
