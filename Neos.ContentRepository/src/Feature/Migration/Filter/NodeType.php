<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Filter;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;

/**
 * Filter nodes by node type.
 */
class NodeType implements NodeAggregateBasedFilterInterface
{
    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * The node type to match on.
     *
     * @var string
     */
    protected $nodeTypeName;

    /**
     * If set to true also all subtypes of the given nodeType will match.
     *
     * @var bool
     */
    protected $withSubTypes = false;

    /**
     * If set this NodeType is actually excluded instead exclusively included.
     *
     * @var bool
     */
    protected $exclude = false;

    public function __construct(NodeTypeManager $nodeTypeManager)
    {
        $this->nodeTypeManager = $nodeTypeManager;
    }

    /**
     * Sets the node type name to match on.
     *
     * @param string $nodeTypeName
     * @return void
     */
    public function setNodeType(string $nodeTypeName): void
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
     * @param bool $withSubTypes
     * @return void
     */
    public function setWithSubTypes(bool $withSubTypes): void
    {
        $this->withSubTypes = $withSubTypes;
    }

    /**
     * Whether the filter should exclude the given NodeType instead of including only this node type.
     *
     * @param bool $exclude
     */
    public function setExclude(bool $exclude): void
    {
        $this->exclude = $exclude;
    }

    public function matches(ReadableNodeAggregateInterface $nodeAggregate): bool
    {
        $nodeTypes = [$this->nodeTypeName];
        if ($this->withSubTypes) {
            foreach ($this->nodeTypeManager->getSubNodeTypes($this->nodeTypeName) as $nodeType) {
                $nodeTypes[] = $nodeType->getName();
            }
        }

        if ($this->exclude) {
            return !in_array($nodeAggregate->getNodeTypeName()->getValue(), $nodeTypes);
        } else {
            // non-negated
            return in_array($nodeAggregate->getNodeTypeName()->getValue(), $nodeTypes);
        }
    }
}
