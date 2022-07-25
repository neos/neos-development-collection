<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\StructureAdjustment;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\EventStore\EventPersister;
use Neos\ContentRepository\EventStore\Events;
use Neos\ContentRepository\EventStore\EventsToPublish;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;

class DisallowedChildNodeAdjustment
{
    use RemoveNodeAggregateTrait;
    use LoadNodeTypeTrait;

    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly ProjectedNodeIterator $projectedNodeIterator,
        private readonly NodeTypeManager $nodeTypeManager,
    ) {
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
                $subgraph = $this->contentRepository->getContentGraph()->getSubgraphByIdentifier(
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
                if (
                    $parentNode !== null
                    && $grandparentNode != null
                    && $parentNode->isTethered()
                    && !is_null($parentNode->getNodeName())
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
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePoint $dimensionSpacePoint
    ): EventsToPublish {
        $referenceOrigin = OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint);
        $events = Events::with(
            new NodeAggregateWasRemoved(
                $nodeAggregate->getContentStreamIdentifier(),
                $nodeAggregate->getIdentifier(),
                $nodeAggregate->occupiesDimensionSpacePoint($referenceOrigin)
                    ? new OriginDimensionSpacePointSet([$referenceOrigin])
                    : new OriginDimensionSpacePointSet([]),
                new DimensionSpacePointSet([$dimensionSpacePoint]),
                UserIdentifier::forSystemUser()
            )
        );

        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $nodeAggregate->getContentStreamIdentifier()
        );

        return new EventsToPublish(
            $streamName->getEventStreamName(),
            $events,
            ExpectedVersion::ANY()
        );
    }

    protected function getNodeTypeManager(): NodeTypeManager
    {
        return $this->nodeTypeManager;
    }
}
