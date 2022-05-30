<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\StructureAdjustment;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\VariantType;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;

#[Flow\Scope('singleton')]
class DimensionAdjustment
{
    protected ProjectedNodeIterator $projectedNodeIterator;
    protected InterDimensionalVariationGraph $interDimensionalVariationGraph;

    public function __construct(
        ProjectedNodeIterator $projectedNodeIterator,
        InterDimensionalVariationGraph $interDimensionalVariationGraph
    ) {
        $this->projectedNodeIterator = $projectedNodeIterator;
        $this->interDimensionalVariationGraph = $interDimensionalVariationGraph;
    }

    /**
     * @return \Generator<int,StructureAdjustment>
     */
    public function findAdjustmentsForNodeType(NodeTypeName $nodeTypeName): \Generator
    {
        foreach ($this->projectedNodeIterator->nodeAggregatesOfType($nodeTypeName) as $nodeAggregate) {
            foreach ($nodeAggregate->getNodes() as $node) {
                foreach (
                    $nodeAggregate->getCoverageByOccupant(
                        $node->getOriginDimensionSpacePoint()
                    ) as $coveredDimensionSpacePoint
                ) {
                    $variantType = $this->interDimensionalVariationGraph->getVariantType(
                        $coveredDimensionSpacePoint,
                        $node->getOriginDimensionSpacePoint()->toDimensionSpacePoint()
                    );
                    if (
                        !$node->getOriginDimensionSpacePoint()->equals($coveredDimensionSpacePoint)
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
                            json_encode($node->getOriginDimensionSpacePoint()->jsonSerialize()),
                            json_encode($coveredDimensionSpacePoint->jsonSerialize()),
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
