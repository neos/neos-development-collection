<?php

namespace Neos\ContentRepository\Domain\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;

/**
 * The node type constraints service
 *
 * @Flow\Scope("singleton")
 * TODO: rename to ..."Factory"
 */
class NodeTypeConstraintService
{
    /**
     * @Flow\Inject
     * @var Domain\Service\NodeTypeManager
     */
    protected $nodeTypeManager;


    public function unserializeFilters(string $serializedFilters): Domain\ValueObject\NodeTypeConstraints
    {
        $wildcardAllowed = empty($serializedFilters);
        $explicitlyAllowedNodeTypeNames = [];
        $explicitlyDisallowedNodeTypeNames = [];

        $nodeTypeFilterParts = Arrays::trimExplode(',', $serializedFilters);
        foreach ($nodeTypeFilterParts as $nodeTypeFilterPart) {
            if (\mb_strpos($nodeTypeFilterPart, '!') === 0) {
                $negate = true;
                $nodeTypeFilterPart = \mb_substr($nodeTypeFilterPart, 1);
            } else {
                $negate = false;
            }
            $nodeTypeFilterPartSubTypes = array_merge([$nodeTypeFilterPart], array_map(function (Domain\Model\NodeType $nodeType) {
                return $nodeType->getName();
            }, $this->nodeTypeManager->getSubNodeTypes($nodeTypeFilterPart, false, true)));

            foreach ($nodeTypeFilterPartSubTypes as $nodeTypeFilterPartSubType) {
                if ($negate) {
                    $explicitlyDisallowedNodeTypeNames[] = $nodeTypeFilterPartSubType;
                } else {
                    $explicitlyAllowedNodeTypeNames[] = $nodeTypeFilterPartSubType;
                }
            }
        }

        return new Domain\ValueObject\NodeTypeConstraints($wildcardAllowed, $explicitlyAllowedNodeTypeNames, $explicitlyDisallowedNodeTypeNames);
    }
}
