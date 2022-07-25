<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\StructureAdjustment;

use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;

class UnknownNodeTypeAdjustment
{
    use RemoveNodeAggregateTrait;
    use LoadNodeTypeTrait;

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
        $nodeType = $this->loadNodeType($nodeTypeName);
        if ($nodeType === null) {
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
                'The node type "' . $nodeTypeName->jsonSerialize()
                    . '" is not found; so the node should be removed (or converted)',
                function () use ($nodeAggregate) {
                    return $this->removeNodeAggregate($nodeAggregate);
                }
            );
        }
    }

    protected function getNodeTypeManager(): NodeTypeManager
    {
        return $this->nodeTypeManager;
    }
}
