<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\StructureAdjustment;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\VariantType;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Intermediary\StructureAdjustment\Dto\StructureAdjustment;

/**
 * @Flow\Scope("singleton")
 */
class DimensionAdjustment
{
    protected ProjectedNodeIterator $projectedNodeIterator;
    protected InterDimensionalVariationGraph $interDimensionalVariationGraph;
    protected RuntimeBlocker $runtimeBlocker;

    public function __construct(
        ProjectedNodeIterator $projectedNodeIterator,
        InterDimensionalVariationGraph $interDimensionalVariationGraph,
        RuntimeBlocker $runtimeBlocker
    ) {
        $this->projectedNodeIterator = $projectedNodeIterator;
        $this->interDimensionalVariationGraph = $interDimensionalVariationGraph;
        $this->runtimeBlocker = $runtimeBlocker;
    }

    public function findAdjustmentsForNodeType(NodeTypeName $nodeTypeName): \Generator
    {
        foreach ($this->projectedNodeIterator->nodeAggregatesOfType($nodeTypeName) as $nodeAggregate) {
            foreach ($nodeAggregate->getNodes() as $node) {
                foreach ($nodeAggregate->getCoverageByOccupant($node->getOriginDimensionSpacePoint()) as $coveredDimensionSpacePoint) {
                    $variantType = $this->interDimensionalVariationGraph->getVariantType($coveredDimensionSpacePoint, $node->getOriginDimensionSpacePoint())->getType();
                    if ($node->getOriginDimensionSpacePoint()->getHash() !== $coveredDimensionSpacePoint->getHash() && $variantType !== VariantType::TYPE_SPECIALIZATION) {
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
                            strtoupper($variantType)
                        );

                        yield StructureAdjustment::createForNode(
                            $node,
                            StructureAdjustment::NODE_COVERS_GENERALIZATION_OR_PEERS,
                            $message,
                            $this->runtimeBlocker
                        );
                    }
                }
            }
        }
    }
}
