<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Domain\NodeType;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;

/**
 * Factory to build a NodeTypeConstraints object, which in turn is needed in
 * TraversableNode::findChildNodes().
 *
 * This factory resolves node type inheritance to a flat node type list.
 *
 * @Flow\Scope("singleton")
 */
class NodeTypeConstraintFactory
{
    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @param string $serializedFilters
     * @return NodeTypeConstraints
     */
    public function parseFilterString(string $serializedFilters): NodeTypeConstraints
    {
        $explicitlyAllowedNodeTypeNames = [];
        $explicitlyDisallowedNodeTypeNames = [];

        $onlyNegatedFilters = true;
        $nodeTypeFilterParts = Arrays::trimExplode(',', $serializedFilters);
        foreach ($nodeTypeFilterParts as $nodeTypeFilterPart) {
            if (\mb_strpos($nodeTypeFilterPart, '!') === 0) {
                $negate = true;
                $nodeTypeFilterPart = \mb_substr($nodeTypeFilterPart, 1);
            } else {
                $onlyNegatedFilters = false;
                $negate = false;
            }
            $nodeTypeFilterPartSubTypes = array_merge([$nodeTypeFilterPart], array_map(function (NodeType $nodeType) {
                return $nodeType->getName();
            }, $this->nodeTypeManager->getSubNodeTypes($nodeTypeFilterPart, true)));

            foreach ($nodeTypeFilterPartSubTypes as $nodeTypeFilterPartSubType) {
                if ($negate) {
                    $explicitlyDisallowedNodeTypeNames[] = $nodeTypeFilterPartSubType;
                } else {
                    $explicitlyAllowedNodeTypeNames[] = $nodeTypeFilterPartSubType;
                }
            }
        }

        // in case there are no filters, we fall back to allowing every node type.
        // Furthermore, if there are only negated filters, we also fall back to allowing every node type (when the excludelist does not match)
        $wildcardAllowed = empty($serializedFilters) || (!empty($serializedFilters) && $onlyNegatedFilters);

        return new NodeTypeConstraints($wildcardAllowed, $explicitlyAllowedNodeTypeNames, $explicitlyDisallowedNodeTypeNames);
    }
}
