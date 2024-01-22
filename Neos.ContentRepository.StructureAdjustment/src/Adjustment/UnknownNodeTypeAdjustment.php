<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment\Adjustment;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;

class UnknownNodeTypeAdjustment
{
    use RemoveNodeAggregateTrait;

    public function __construct(
        private readonly ProjectedNodeIterator $projectedNodeIterator,
        private readonly NodeTypeManager $nodeTypeManager
    ) {
    }

    /**
     * @return \Generator<int,StructureAdjustment>
     */
    public function findAdjustmentsForNodeType(NodeTypeName $nodeTypeName): \Generator
    {
        if (!$this->nodeTypeManager->hasNodeType($nodeTypeName)) {
            // node type is not existing right now.
            yield from $this->removeAllNodesOfType($nodeTypeName);
        }
    }

    /**
     * @return \Generator<int,StructureAdjustment>
     */
    private function removeAllNodesOfType(NodeTypeName $nodeTypeName): \Generator
    {
        foreach ($this->projectedNodeIterator->nodeAggregatesOfType($nodeTypeName) as $nodeAggregate) {
            yield StructureAdjustment::createForNodeAggregate(
                $nodeAggregate,
                StructureAdjustment::NODE_TYPE_MISSING,
                'The node type "' . $nodeTypeName->value
                    . '" is not found; so the node should be removed (or converted)',
                function () use ($nodeAggregate) {
                    return $this->removeNodeAggregate($nodeAggregate);
                }
            );
        }
    }
}
