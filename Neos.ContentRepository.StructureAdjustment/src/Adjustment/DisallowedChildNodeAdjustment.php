<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment\Adjustment;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

class DisallowedChildNodeAdjustment
{
    use RemoveNodeAggregateTrait;

    public function __construct(
        private readonly ContentGraphInterface $contentGraph,
        private readonly NodeTypeManager $nodeTypeManager,
    ) {
    }

    /**
     * @return \Generator<int,StructureAdjustment>
     */
    public function findAdjustmentsForNodeType(NodeTypeName $nodeTypeName): \Generator
    {
        if (!$this->nodeTypeManager->hasNodeType($nodeTypeName)) {
            // no adjustments for unknown node types
            return;
        }

        foreach ($this->contentGraph->findNodeAggregatesByType($nodeTypeName) as $nodeAggregate) {
            $nodeType = $this->nodeTypeManager->getNodeType($nodeAggregate->nodeTypeName);
            if (!$nodeType) {
                // unknown child node type, so we skip this test as we won't be able to find out node type constraints
                continue;
            }

            // Here, we iterate over the covered dimension space points of the node aggregate one by one;
            // as it can happen that the constraint is only violated in e.g. "AT", but not in "DE".
            // Then, we only want to remove the single edge.
            foreach ($nodeAggregate->coveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
                $subgraph = $this->contentGraph->getSubgraph(
                    $coveredDimensionSpacePoint,
                    VisibilityConstraints::withoutRestrictions()
                );

                $parentNode = $subgraph->findParentNode($nodeAggregate->nodeAggregateId);
                $grandparentNode = $parentNode !== null
                    ? $subgraph->findParentNode($parentNode->aggregateId)
                    : null;

                $allowedByParent = true;
                $parentNodeType = null;
                if ($parentNode !== null) {
                    $parentNodeType = $this->nodeTypeManager->getNodeType($parentNode->nodeTypeName);
                    if ($parentNodeType) {
                        $allowedByParent = $parentNodeType->allowsChildNodeType($nodeType)
                            || ($nodeAggregate->nodeName && $parentNodeType->tetheredNodeTypeDefinitions->contain($nodeAggregate->nodeName));
                    }
                }

                $allowedByGrandparent = false;
                if (
                    $parentNode !== null
                    && $grandparentNode !== null
                    && $parentNode->classification->isTethered()
                    && !is_null($parentNode->name)
                ) {
                    $allowedByGrandparent = $this->nodeTypeManager->isNodeTypeAllowedAsChildToTetheredNode(
                        $grandparentNode->nodeTypeName,
                        $parentNode->name,
                        $nodeAggregate->nodeTypeName
                    );
                }

                if (!$allowedByParent && !$allowedByGrandparent) {
                    $node = $subgraph->findNodeById($nodeAggregate->nodeAggregateId);
                    if (is_null($node)) {
                        continue;
                    }

                    $message = sprintf(
                        '
                    The parent node type "%s" is not allowing children of type "%s",
                    and the grandparent node type "%s" is not allowing grandchildren of type "%s".
                    Thus, the node is invalid at this location and should be removed.
                ',
                        $parentNode?->nodeTypeName->value ?? '',
                        $node->nodeTypeName->value,
                        $grandparentNode?->nodeTypeName->value ?? '',
                        $node->nodeTypeName->value,
                    );

                    yield StructureAdjustment::createForNode(
                        $node,
                        StructureAdjustment::DISALLOWED_CHILD_NODE,
                        $message,
                        function () use ($nodeAggregate, $coveredDimensionSpacePoint) {
                            return $this->removeNodeInSingleDimensionSpacePoint(
                                $nodeAggregate,
                                $coveredDimensionSpacePoint
                            );
                        }
                    );
                }
            }
        }
    }


    private function removeNodeInSingleDimensionSpacePoint(
        NodeAggregate $nodeAggregate,
        DimensionSpacePoint $dimensionSpacePoint
    ): EventsToPublish {
        $referenceOrigin = OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint);
        $events = Events::with(
            new NodeAggregateWasRemoved(
                $this->contentGraph->getWorkspaceName(),
                $this->contentGraph->getContentStreamId(),
                $nodeAggregate->nodeAggregateId,
                $nodeAggregate->occupiesDimensionSpacePoint($referenceOrigin)
                    ? new OriginDimensionSpacePointSet([$referenceOrigin])
                    : new OriginDimensionSpacePointSet([]),
                new DimensionSpacePointSet([$dimensionSpacePoint]),
            )
        );

        $streamName = ContentStreamEventStreamName::fromContentStreamId(
            $this->contentGraph->getContentStreamId()
        );

        return new EventsToPublish(
            $streamName->getEventStreamName(),
            $events,
            ExpectedVersion::ANY()
        );
    }
}
