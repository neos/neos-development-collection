<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\StructureAdjustment;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Feature\StructureAdjustment\LoadNodeTypeTrait;
use Neos\ContentRepository\Feature\StructureAdjustment\RemoveNodeAggregateTrait;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\Feature\StructureAdjustment\StructureAdjustment;
use Neos\EventSourcing\EventStore\EventStore;
use Ramsey\Uuid\Uuid;

#[Flow\Scope("singleton")]
class DisallowedChildNodeAdjustment
{
    use RemoveNodeAggregateTrait;
    use LoadNodeTypeTrait;

    protected EventStore $eventStore;

    protected ProjectedNodeIterator $projectedNodeIterator;

    protected NodeTypeManager $nodeTypeManager;

    protected ContentGraphInterface $contentGraph;

    protected ReadSideMemoryCacheManager $readSideMemoryCacheManager;

    protected RuntimeBlocker $runtimeBlocker;

    public function __construct(
        EventStore $eventStore,
        ProjectedNodeIterator $projectedNodeIterator,
        NodeTypeManager $nodeTypeManager,
        ContentGraphInterface $contentGraph,
        ReadSideMemoryCacheManager $readSideMemoryCacheManager,
        RuntimeBlocker $runtimeBlocker
    ) {
        $this->eventStore = $eventStore;
        $this->projectedNodeIterator = $projectedNodeIterator;
        $this->nodeTypeManager = $nodeTypeManager;
        $this->contentGraph = $contentGraph;
        $this->readSideMemoryCacheManager = $readSideMemoryCacheManager;
        $this->runtimeBlocker = $runtimeBlocker;
    }

    public function getRuntimeBlocker(): RuntimeBlocker
    {
        return $this->runtimeBlocker;
    }

    /**
     * @return \Generator<int,StructureAdjustment>
     */
    public function findAdjustmentsForNodeType(NodeTypeName $nodeTypeName): \Generator
    {
        $nodeType = $this->loadNodeType($nodeTypeName);

        if ($nodeType === null) {
            // no adjustments for unknown node types
            return;
        }

        foreach ($this->projectedNodeIterator->nodeAggregatesOfType($nodeTypeName) as $nodeAggregate) {
            $nodeType = $this->loadNodeType($nodeAggregate->getNodeTypeName());
            if ($nodeType === null) {
                // unknown child node type, so we skip this test as we won't be able to find out node type constraints
                continue;
            }

            // Here, we iterate over the covered dimension space points of the node aggregate one by one;
            // as it can happen that the constraint is only violated in e.g. "AT", but not in "DE".
            // Then, we only want to remove the single edge.
            foreach ($nodeAggregate->getCoveredDimensionSpacePoints() as $coveredDimensionSpacePoint) {
                $subgraph = $this->contentGraph->getSubgraphByIdentifier(
                    $nodeAggregate->getContentStreamIdentifier(),
                    $coveredDimensionSpacePoint,
                    VisibilityConstraints::withoutRestrictions()
                );

                $parentNode = $subgraph->findParentNode($nodeAggregate->getIdentifier());
                $grandparentNode = $parentNode !== null
                    ? $subgraph->findParentNode($parentNode->getNodeAggregateIdentifier())
                    : null;


                $allowedByParent = true;
                $parentNodeType = null;
                if ($parentNode !== null) {
                    $parentNodeType = $this->loadNodeType($parentNode->getNodeTypeName());
                    if ($parentNodeType !== null) {
                        $allowedByParent = $parentNodeType->allowsChildNodeType($nodeType);
                    }
                }

                $allowedByGrandparent = false;
                $grandparentNodeType = null;
                if ($parentNode !== null && $grandparentNode != null
                    && $parentNode->isTethered() && !is_null($parentNode->getNodeName())
                ) {
                    $grandparentNodeType = $this->loadNodeType($grandparentNode->getNodeTypeName());
                    if ($grandparentNodeType !== null) {
                        $allowedByGrandparent = $grandparentNodeType->allowsGrandchildNodeType(
                            $parentNode->getNodeName()->jsonSerialize(),
                            $nodeType
                        );
                    }
                }

                if (!$allowedByParent && !$allowedByGrandparent) {
                    $node = $subgraph->findNodeByNodeAggregateIdentifier($nodeAggregate->getIdentifier());
                    if (is_null($node)) {
                        continue;
                    }

                    $message = sprintf(
                        '
                        The parent node type "%s" is not allowing children of type "%s",
                        and the grandparent node type "%s" is not allowing grandchildren of type "%s".
                        Thus, the node is invalid at this location and should be removed.
                    ',
                        $parentNodeType !== null ? $parentNodeType->getName() : '',
                        $node->getNodeTypeName()->jsonSerialize(),
                        $grandparentNodeType !== null ? $grandparentNodeType->getName() : '',
                        $node->getNodeTypeName()->jsonSerialize(),
                    );

                    yield StructureAdjustment::createForNode(
                        $node,
                        StructureAdjustment::DISALLOWED_CHILD_NODE,
                        $message,
                        function () use ($nodeAggregate, $coveredDimensionSpacePoint) {
                            $this->readSideMemoryCacheManager->disableCache();
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

    protected function getEventStore(): EventStore
    {
        return $this->eventStore;
    }

    private function removeNodeInSingleDimensionSpacePoint(
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePoint $dimensionSpacePoint
    ): CommandResult {
        $referenceOrigin = OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint);
        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new NodeAggregateWasRemoved(
                    $nodeAggregate->getContentStreamIdentifier(),
                    $nodeAggregate->getIdentifier(),
                    $nodeAggregate->occupiesDimensionSpacePoint($referenceOrigin)
                        ? new OriginDimensionSpacePointSet([$referenceOrigin])
                        : new OriginDimensionSpacePointSet([]),
                    new DimensionSpacePointSet([$dimensionSpacePoint]),
                    UserIdentifier::forSystemUser()
                ),
                Uuid::uuid4()->toString()
            )
        );

        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $nodeAggregate->getContentStreamIdentifier()
        );
        $this->getEventStore()->commit($streamName->getEventStreamName(), $events);

        return CommandResult::fromPublishedEvents($events, $this->runtimeBlocker);
    }

    protected function getNodeTypeManager(): NodeTypeManager
    {
        return $this->nodeTypeManager;
    }
}
