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
        $constraints = [
            'excludeNodeTypes' => [],
            'includeNodeTypes' => []
        ];

        $nodeTypeFilterParts = Arrays::trimExplode(',', $serializedFilters);
        foreach ($nodeTypeFilterParts as $nodeTypeFilterPart) {
            $nodeTypeFilterPart = trim($nodeTypeFilterPart);
            if (strpos($nodeTypeFilterPart, '!') === 0) {
                $negate = true;
                $nodeTypeFilterPart = substr($nodeTypeFilterPart, 1);
            } else {
                $negate = false;
            }
            $nodeTypeFilterPartSubTypes = array_merge([$nodeTypeFilterPart], array_map(function(Domain\Model\NodeType $nodeType) {
                return $nodeType->getName();
            }, $this->nodeTypeManager->getSubNodeTypes($nodeTypeFilterPart)));

            foreach ($nodeTypeFilterPartSubTypes as $nodeTypeFilterPartSubType) {
                if ($negate === true) {
                    $constraints['excludeNodeTypes'][] = $nodeTypeFilterPartSubType;
                } else {
                    $constraints['includeNodeTypes'][] = $nodeTypeFilterPartSubType;
                }
            }
        }

        return new Domain\ValueObject\NodeTypeConstraints($constraints);
    }
}
