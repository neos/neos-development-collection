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
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Persistence\Doctrine\Query;

/**
 * Filter nodes by node type.
 */
class NodeType implements DoctrineFilterInterface
{
    /**
     * @var NodeTypeManager
     * @Flow\Inject
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
     * @param bool $withSubTypes
     * @return void
     */
    public function setWithSubTypes($withSubTypes)
    {
        $this->withSubTypes = $withSubTypes;
    }

    /**
     * Whether the filter should exclude the given NodeType instead of including only this node type.
     *
     * @param bool $exclude
     */
    public function setExclude(bool $exclude)
    {
        $this->exclude = $exclude;
    }

    /**
     * @param Query $baseQuery
     * @return array
     * @throws \Neos\Flow\Persistence\Exception\InvalidQueryException
     */
    public function getFilterExpressions(Query $baseQuery): array
    {
        $nodeTypes = [$this->nodeTypeName];
        if ($this->withSubTypes) {
            foreach ($this->nodeTypeManager->getSubNodeTypes($this->nodeTypeName) as $nodeType) {
                $nodeTypes[] = $nodeType->getName();
            }
        }

        $filterExpressions = [];

        if ($this->exclude) {
            $filterExpressions[] = $baseQuery->logicalNot($baseQuery->in('nodeType', $nodeTypes));
        } else {
            $filterExpressions[] = $baseQuery->in('nodeType', $nodeTypes);
        }

        return $filterExpressions;
    }
}
