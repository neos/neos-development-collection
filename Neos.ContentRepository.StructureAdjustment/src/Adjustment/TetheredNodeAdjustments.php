<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment\Adjustment;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\CoverageNodeMoveMapping;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\CoverageNodeMoveMappings;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Feature\Common\TetheredNodeInternals;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\SucceedingSiblingNodeMoveDestination;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\OriginNodeMoveMapping;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\OriginNodeMoveMappings;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\Common\NodeVariationInternals;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;

class TetheredNodeAdjustments
{
    use NodeVariationInternals;
    use RemoveNodeAggregateTrait;
    use TetheredNodeInternals;

    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly ProjectedNodeIterator $projectedNodeIterator,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly DimensionSpace\InterDimensionalVariationGraph $interDimensionalVariationGraph,
    ) {
    }

    /**
     * @return \Generator<int,StructureAdjustment>
     */
    public function findAdjustmentsForNodeType(NodeTypeName $nodeTypeName): \Generator
    {
        if (!$this->nodeTypeManager->hasNodeType($nodeTypeName)) {
            // In case we cannot find the expected tethered nodes, this fix cannot do anything.
            return;
        }
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
        $expectedTetheredNodes = $this->nodeTypeManager->getTetheredNodesConfigurationForNodeType($nodeType);

        foreach ($this->projectedNodeIterator->nodeAggregatesOfType($nodeTypeName) as $nodeAggregate) {
            // find missing tethered nodes
            $foundMissingOrDisallowedTetheredNodes = false;
            $originDimensionSpacePoints = $nodeType->isOfType(NodeTypeName::ROOT_NODE_TYPE_NAME)
                ? DimensionSpace\OriginDimensionSpacePointSet::fromDimensionSpacePointSet(
                    DimensionSpace\DimensionSpacePointSet::fromArray($this->getInterDimensionalVariationGraph()->getRootGeneralizations())
                )
                : $nodeAggregate->occupiedDimensionSpacePoints;

            foreach ($originDimensionSpacePoints as $originDimensionSpacePoint) {
                foreach ($expectedTetheredNodes as $tetheredNodeName => $expectedTetheredNodeType) {
                    $tetheredNodeName = NodeName::fromString($tetheredNodeName);

                    $subgraph = $this->contentRepository->getContentGraph()->getSubgraph(
                        $nodeAggregate->contentStreamId,
                        $originDimensionSpacePoint->toDimensionSpacePoint(),
                        VisibilityConstraints::withoutRestrictions()
                    );
                    $tetheredNode = $subgraph->findChildNodeConnectedThroughEdgeName(
                        $nodeAggregate->nodeAggregateId,
                        $tetheredNodeName
                    );
                    if ($tetheredNode === null) {
                        $foundMissingOrDisallowedTetheredNodes = true;
                        // $nestedNode not found
                        // - so a tethered node is missing in the OriginDimensionSpacePoint of the $node
                        yield StructureAdjustment::createForNodeIdentity(
                            $nodeAggregate->contentStreamId,
                            $originDimensionSpacePoint,
                            $nodeAggregate->nodeAggregateId,
                            StructureAdjustment::TETHERED_NODE_MISSING,
                            'The tethered child node "' . $tetheredNodeName->value . '" is missing.',
                            function () use ($nodeAggregate, $originDimensionSpacePoint, $tetheredNodeName, $expectedTetheredNodeType) {
                                $events = $this->createEventsForMissingTetheredNode(
                                    $nodeAggregate,
                                    $originDimensionSpacePoint,
                                    $tetheredNodeName,
                                    null,
                                    $expectedTetheredNodeType,
                                    $this->contentRepository
                                );

                                $streamName = ContentStreamEventStreamName::fromContentStreamId($nodeAggregate->contentStreamId);
                                return new EventsToPublish(
                                    $streamName->getEventStreamName(),
                                    $events,
                                        ExpectedVersion::ANY()
                                );
                            }
                        );
                    } else {
                        yield from $this->ensureNodeIsTethered($tetheredNode);
                        yield from $this->ensureNodeIsOfType($tetheredNode, $expectedTetheredNodeType);
                    }
                }
            }

            // find disallowed tethered nodes
            $tetheredNodeAggregates = $this->contentRepository->getContentGraph()->findTetheredChildNodeAggregates(
                $nodeAggregate->contentStreamId,
                $nodeAggregate->nodeAggregateId
            );
            foreach ($tetheredNodeAggregates as $tetheredNodeAggregate) {
                assert($tetheredNodeAggregate->nodeName !== null); // it's tethered!
                if (!isset($expectedTetheredNodes[$tetheredNodeAggregate->nodeName->value])) {
                    $foundMissingOrDisallowedTetheredNodes = true;
                    yield StructureAdjustment::createForNodeAggregate(
                        $tetheredNodeAggregate,
                        StructureAdjustment::DISALLOWED_TETHERED_NODE,
                        'The tethered child node "'
                            . $tetheredNodeAggregate->nodeName->value . '" should be removed.',
                        function () use ($tetheredNodeAggregate) {
                            return $this->removeNodeAggregate($tetheredNodeAggregate);
                        }
                    );
                }
            }

            // find wrongly ordered tethered nodes
            if ($foundMissingOrDisallowedTetheredNodes === false) {
                foreach ($originDimensionSpacePoints as $originDimensionSpacePoint) {
                    $subgraph = $this->contentRepository->getContentGraph()->getSubgraph(
                        $nodeAggregate->contentStreamId,
                        $originDimensionSpacePoint->toDimensionSpacePoint(),
                        VisibilityConstraints::withoutRestrictions()
                    );
                    $childNodes = $subgraph->findChildNodes($nodeAggregate->nodeAggregateId, FindChildNodesFilter::create());

                    /** is indexed by node name, and the value is the tethered node itself */
                    $actualTetheredChildNodes = [];
                    foreach ($childNodes as $childNode) {
                        if ($childNode->classification->isTethered()) {
                            assert($childNode->nodeName !== null); // it's tethered!
                            $actualTetheredChildNodes[$childNode->nodeName->value] = $childNode;
                        }
                    }

                    if (array_keys($actualTetheredChildNodes) !== array_keys($expectedTetheredNodes)) {
                        // we need to re-order: We go from the last to the first
                        yield StructureAdjustment::createForNodeIdentity(
                            $nodeAggregate->contentStreamId,
                            $originDimensionSpacePoint,
                            $nodeAggregate->nodeAggregateId,
                            StructureAdjustment::TETHERED_NODE_WRONGLY_ORDERED,
                            'Tethered nodes wrongly ordered, expected: '
                                . implode(', ', array_keys($expectedTetheredNodes))
                                . ' - actual: '
                                . implode(', ', array_keys($actualTetheredChildNodes)),
                            fn () => $this->reorderNodes(
                                $nodeAggregate->contentStreamId,
                                $nodeAggregate->nodeAggregateId,
                                $originDimensionSpacePoint,
                                $actualTetheredChildNodes,
                                array_keys($expectedTetheredNodes)
                            )
                        );
                    }
                }
            }
        }
    }

    /**
     * @return \Generator<int,StructureAdjustment>
     */
    private function ensureNodeIsTethered(Node $node): \Generator
    {
        if (!$node->classification->isTethered()) {
            yield StructureAdjustment::createForNode(
                $node,
                StructureAdjustment::NODE_IS_NOT_TETHERED_BUT_SHOULD_BE,
                'This node should be a tethered node, but is not.  This can not be fixed automatically right now (TODO)'
            );
        }
    }

    /**
     * @return \Generator<int,StructureAdjustment>
     */
    private function ensureNodeIsOfType(Node $node, NodeType $expectedNodeType): \Generator
    {
        if ($node->nodeTypeName->value !== $expectedNodeType->name->value) {
            yield StructureAdjustment::createForNode(
                $node,
                StructureAdjustment::TETHERED_NODE_TYPE_WRONG,
                'should be of type "' . $expectedNodeType->name->value . '", but was "' . $node->nodeTypeName->value . '".'
            );
        }
    }

    protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph
    {
        return $this->interDimensionalVariationGraph;
    }

    /**
     * array key: name of tethered child node. Value: the Node itself.
     * @param array<string,Node> $actualTetheredChildNodes
     * an array depicting the expected tethered order, like ["node1", "node2"]
     * @param array<int,string> $expectedNodeOrdering
     */
    private function reorderNodes(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId,
        DimensionSpace\OriginDimensionSpacePoint $originDimensionSpacePoint,
        array $actualTetheredChildNodes,
        array $expectedNodeOrdering
    ): EventsToPublish {
        $events = [];

        // we move from back to front through the expected ordering; as we always specify the **succeeding** sibling.
        $succeedingSiblingNodeName = array_pop($expectedNodeOrdering);
        while ($nodeNameToMove = array_pop($expectedNodeOrdering)) {
            // let's move $nodeToMove before $succeedingNode.
            /* @var $nodeToMove Node */
            $nodeToMove = $actualTetheredChildNodes[$nodeNameToMove];
            /* @var $succeedingNode Node */
            $succeedingNode = $actualTetheredChildNodes[$succeedingSiblingNodeName];

            $events[] = new NodeAggregateWasMoved(
                $contentStreamId,
                $nodeToMove->nodeAggregateId,
                OriginNodeMoveMappings::fromArray([
                    new OriginNodeMoveMapping(
                        $nodeToMove->originDimensionSpacePoint,
                        CoverageNodeMoveMappings::create(
                            CoverageNodeMoveMapping::createForNewSucceedingSibling(
                                // TODO: I am not sure the next line is 100% correct. IMHO this must be the COVERED
                                // TODO: DimensionSpacePoint (though I am not sure whether we have that one now)
                                $nodeToMove->originDimensionSpacePoint->toDimensionSpacePoint(),
                                SucceedingSiblingNodeMoveDestination::create(
                                    $succeedingNode->nodeAggregateId,
                                    $succeedingNode->originDimensionSpacePoint,
                                    // we only change the order, not the parent -> so we can simply use the parent here.
                                    $parentNodeAggregateId,
                                    $originDimensionSpacePoint
                                )
                            )
                        )
                    )
                ]),
            );

            // now, go one step left.
            $succeedingSiblingNodeName = $nodeNameToMove;
        }

        $streamName = ContentStreamEventStreamName::fromContentStreamId($contentStreamId);
        return new EventsToPublish(
            $streamName->getEventStreamName(),
            Events::fromArray($events),
            ExpectedVersion::ANY()
        );
    }
}
