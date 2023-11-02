<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment\Adjustment;

use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\DimensionSpace\VariantType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;

class DimensionAdjustment
{
    public function __construct(
        protected ProjectedNodeIterator $projectedNodeIterator,
        protected InterDimensionalVariationGraph $interDimensionalVariationGraph,
        protected NodeTypeManager $nodeTypeManager,
    ) {
    }

    /**
     * @return iterable<int,StructureAdjustment>
     */
    public function findAdjustmentsForNodeType(NodeTypeName $nodeTypeName): iterable
    {
        try {
            $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
        } catch (NodeTypeNotFoundException) {
            return [];
        }
        if ($nodeType->isOfType(NodeTypeName::ROOT_NODE_TYPE_NAME)) {
            return [];
        }
        foreach ($this->projectedNodeIterator->nodeAggregatesOfType($nodeTypeName) as $nodeAggregate) {
            foreach ($nodeAggregate->getNodes() as $node) {
                foreach (
                    $nodeAggregate->getCoverageByOccupant(
                        $node->originDimensionSpacePoint
                    ) as $coveredDimensionSpacePoint
                ) {
                    $variantType = $this->interDimensionalVariationGraph->getVariantType(
                        $coveredDimensionSpacePoint,
                        $node->originDimensionSpacePoint->toDimensionSpacePoint()
                    );
                    if (
                        !$node->originDimensionSpacePoint->equals($coveredDimensionSpacePoint)
                        && $variantType !== VariantType::TYPE_SPECIALIZATION
                    ) {
                        $message = sprintf(
                            '
                                The node has an Origin Dimension Space Point of %s,
                                and a covered dimension space point (i.e. an incoming edge) in %s.

                                The incoming edge is a %s of the OriginDimensionSpacePoint, which is
                                a violated invariant.

                                You need to write a node migration to update the database to fix this case.
                            ',
                            $node->originDimensionSpacePoint->toJson(),
                            $coveredDimensionSpacePoint->toJson(),
                            strtoupper($variantType->value)
                        );

                        yield StructureAdjustment::createForNode(
                            $node,
                            StructureAdjustment::NODE_COVERS_GENERALIZATION_OR_PEERS,
                            $message
                        );
                    }
                }
            }
        }
    }
}
