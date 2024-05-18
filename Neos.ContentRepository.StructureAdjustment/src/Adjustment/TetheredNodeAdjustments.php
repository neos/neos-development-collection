<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment\Adjustment;

use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSibling;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\Common\NodeVariationInternals;
use Neos\ContentRepository\Core\Feature\Common\TetheredNodeInternals;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

class TetheredNodeAdjustments
{
    use NodeVariationInternals;
    use RemoveNodeAggregateTrait;
    use TetheredNodeInternals;

    public function __construct(
        private readonly ProjectedNodeIterator $projectedNodeIterator,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly DimensionSpace\InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private readonly PropertyConverter $propertyConverter
    ) {
    }

    /**
     * @return \Generator<int,StructureAdjustment>
     */
    public function findAdjustmentsForNodeType(NodeTypeName $nodeTypeName): \Generator
    {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
        if (!$nodeType) {
            // In case we cannot find the expected tethered nodes, this fix cannot do anything.
            return;
        }
        foreach ($this->projectedNodeIterator->nodeAggregatesOfType($nodeTypeName) as $nodeAggregate) {
            // find missing tethered nodes
            $foundMissingOrDisallowedTetheredNodes = false;
            $originDimensionSpacePoints = $nodeType->isOfType(NodeTypeName::ROOT_NODE_TYPE_NAME)
                ? DimensionSpace\OriginDimensionSpacePointSet::fromDimensionSpacePointSet(
                    DimensionSpace\DimensionSpacePointSet::fromArray($this->getInterDimensionalVariationGraph()->getRootGeneralizations())
                )
                : $nodeAggregate->occupiedDimensionSpacePoints;

            foreach ($originDimensionSpacePoints as $originDimensionSpacePoint) {
                foreach ($nodeType->tetheredNodeTypeDefinitions as $tetheredNodeTypeDefinition) {
                    $tetheredNode = $this->projectedNodeIterator->contentGraph->getSubgraph(
                        $originDimensionSpacePoint->toDimensionSpacePoint(),
                        VisibilityConstraints::withoutRestrictions()
                    )->findNodeByPath(
                        $tetheredNodeTypeDefinition->name,
                        $nodeAggregate->nodeAggregateId
                    );
                    if ($tetheredNode === null) {
                        $foundMissingOrDisallowedTetheredNodes = true;
                        // $nestedNode not found
                        // - so a tethered node is missing in the OriginDimensionSpacePoint of the $node
                        yield StructureAdjustment::createForNodeIdentity(
                            $nodeAggregate->workspaceName,
                            $originDimensionSpacePoint,
                            $nodeAggregate->nodeAggregateId,
                            StructureAdjustment::TETHERED_NODE_MISSING,
                            'The tethered child node "' . $tetheredNodeTypeDefinition->name->value . '" is missing.',
                            function () use ($nodeAggregate, $originDimensionSpacePoint, $tetheredNodeTypeDefinition) {
                                $events = $this->createEventsForMissingTetheredNode(
                                    $this->projectedNodeIterator->contentGraph,
                                    $nodeAggregate,
                                    $originDimensionSpacePoint,
                                    $tetheredNodeTypeDefinition,
                                    null
                                );

                                $streamName = ContentStreamEventStreamName::fromContentStreamId(
                                    $this->projectedNodeIterator->contentGraph->getContentStreamId()
                                );
                                return new EventsToPublish(
                                    $streamName->getEventStreamName(),
                                    $events,
                                        ExpectedVersion::ANY()
                                );
                            }
                        );
                    } else {
                        yield from $this->ensureNodeIsTethered($tetheredNode);
                        yield from $this->ensureNodeIsOfType($tetheredNode, $tetheredNodeTypeDefinition->nodeTypeName);
                    }
                }
            }

            // find disallowed tethered nodes
            $tetheredNodeAggregates = $this->projectedNodeIterator->contentGraph->findTetheredChildNodeAggregates(
                $nodeAggregate->nodeAggregateId
            );
            foreach ($tetheredNodeAggregates as $tetheredNodeAggregate) {
                assert($tetheredNodeAggregate->nodeName !== null); // it's tethered!
                if (!$nodeType->tetheredNodeTypeDefinitions->contain($tetheredNodeAggregate->nodeName)) {
                    $foundMissingOrDisallowedTetheredNodes = true;
                    yield StructureAdjustment::createForNodeAggregate(
                        $tetheredNodeAggregate,
                        StructureAdjustment::DISALLOWED_TETHERED_NODE,
                        'The tethered child node "'
                            . $tetheredNodeAggregate->nodeName->value . '" should be removed.',
                        function () use ($tetheredNodeAggregate) {
                            return $this->removeNodeAggregate($this->projectedNodeIterator->contentGraph, $tetheredNodeAggregate);
                        }
                    );
                }
            }

            // find wrongly ordered tethered nodes
            if ($foundMissingOrDisallowedTetheredNodes === false) {
                foreach ($originDimensionSpacePoints as $originDimensionSpacePoint) {
                    $childNodes = $this->projectedNodeIterator->contentGraph->getSubgraph($originDimensionSpacePoint->toDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions())->findChildNodes($nodeAggregate->nodeAggregateId, FindChildNodesFilter::create());

                    /** is indexed by node name, and the value is the tethered node itself */
                    $actualTetheredChildNodes = [];
                    foreach ($childNodes as $childNode) {
                        if ($childNode->classification->isTethered()) {
                            assert($childNode->nodeName !== null); // it's tethered!
                            $actualTetheredChildNodes[$childNode->nodeName->value] = $childNode;
                        }
                    }

                    if (array_keys($actualTetheredChildNodes) !== array_keys($nodeType->tetheredNodeTypeDefinitions->toArray())) {
                        // we need to re-order: We go from the last to the first
                        yield StructureAdjustment::createForNodeIdentity(
                            $nodeAggregate->workspaceName,
                            $originDimensionSpacePoint,
                            $nodeAggregate->nodeAggregateId,
                            StructureAdjustment::TETHERED_NODE_WRONGLY_ORDERED,
                            'Tethered nodes wrongly ordered, expected: '
                                . implode(', ', array_keys($nodeType->tetheredNodeTypeDefinitions->toArray()))
                                . ' - actual: '
                                . implode(', ', array_keys($actualTetheredChildNodes)),
                            fn () => $this->reorderNodes(
                                $this->projectedNodeIterator->contentGraph->getWorkspaceName(),
                                $this->projectedNodeIterator->contentGraph->getContentStreamId(),
                                $nodeAggregate->getCoverageByOccupant($originDimensionSpacePoint),
                                $actualTetheredChildNodes,
                                array_keys($nodeType->tetheredNodeTypeDefinitions->toArray())
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
    private function ensureNodeIsOfType(Node $node, NodeTypeName $expectedNodeTypeName): \Generator
    {
        if ($node->nodeTypeName->value !== $expectedNodeTypeName->value) {
            yield StructureAdjustment::createForNode(
                $node,
                StructureAdjustment::TETHERED_NODE_TYPE_WRONG,
                'should be of type "' . $expectedNodeTypeName->value . '", but was "' . $node->nodeTypeName->value . '".'
            );
        }
    }

    protected function getInterDimensionalVariationGraph(): DimensionSpace\InterDimensionalVariationGraph
    {
        return $this->interDimensionalVariationGraph;
    }

    protected function getPropertyConverter(): PropertyConverter
    {
        return $this->propertyConverter;
    }

    /**
     * array key: name of tethered child node. Value: the Node itself.
     * @param array<string,Node> $actualTetheredChildNodes
     * an array depicting the expected tethered order, like ["node1", "node2"]
     * @param array<int,string> $expectedNodeOrdering
     */
    private function reorderNodes(
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        DimensionSpace\DimensionSpacePointSet $coverageByOrigin,
        array $actualTetheredChildNodes,
        array $expectedNodeOrdering
    ): EventsToPublish {
        $events = [];

        // we move from back to front through the expected ordering; as we always specify the **succeeding** sibling.
        $succeedingSiblingNodeName = array_pop($expectedNodeOrdering);
        while ($nodeNameToMove = array_pop($expectedNodeOrdering)) {
            // let's move $nodeToMove before $succeedingNode.
            $nodeToMove = $actualTetheredChildNodes[$nodeNameToMove];
            $succeedingNode = $actualTetheredChildNodes[$succeedingSiblingNodeName];

            $succeedingSiblingsForCoverage = [];
            foreach ($coverageByOrigin as $coveredDimensionSpacePoint) {
                $succeedingSiblingsForCoverage[] = new InterdimensionalSibling(
                    $coveredDimensionSpacePoint,
                    $succeedingNode->nodeAggregateId
                );
            }

            $events[] = new NodeAggregateWasMoved(
                $workspaceName,
                $contentStreamId,
                $nodeToMove->nodeAggregateId,
                null,
                new InterdimensionalSiblings(...$succeedingSiblingsForCoverage),
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
