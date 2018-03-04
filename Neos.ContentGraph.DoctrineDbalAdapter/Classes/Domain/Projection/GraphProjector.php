<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Domain\Context\Node\Event;
use Neos\ContentRepository\Domain as ContentRepository;
use Neos\ContentRepository\Domain\Context\Node\Event\NodePropertyWasSet;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeWasHidden;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeWasShown;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain\ValueObject\PropertyName;
use Neos\ContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\EventSourcing\Projection\ProjectorInterface;
use Neos\Flow\Annotations as Flow;

/**
 * The alternate reality-aware graph projector for the Doctrine backend
 *
 * @Flow\Scope("singleton")
 */
class GraphProjector implements ProjectorInterface
{
    /**
     * @Flow\Inject
     * @var ProjectionContentGraph
     */
    protected $projectionContentGraph;

    /**
     * @Flow\Inject
     * @var DbalClient
     */
    protected $client;

    /**
     * @Flow\Signal
     */
    public function emitProjectionUpdated() {
    }

    public function reset(): void
    {
        $this->getDatabaseConnection()->transactional(function () {
            $this->getDatabaseConnection()->executeQuery('TRUNCATE table neos_contentgraph_node');
            $this->getDatabaseConnection()->executeQuery('TRUNCATE table neos_contentgraph_hierarchyrelation');
            $this->getDatabaseConnection()->executeQuery('TRUNCATE table neos_contentgraph_referencerelation');
        });
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->projectionContentGraph->isEmpty();
    }

    /**
     * @param Event\RootNodeWasCreated $event
     */
    final public function whenRootNodeWasCreated(Event\RootNodeWasCreated $event)
    {
        $nodeRelationAnchorPoint = new NodeRelationAnchorPoint();
        $node = new Node(
            $nodeRelationAnchorPoint,
            $event->getNodeIdentifier(),
            null,
            null,
            null,
            [],
            $event->getNodeTypeName()
        );

        $this->transactional(function () use ($node) {
            $node->addToDatabase($this->getDatabaseConnection());
        });
    }

    /**
     * @param Event\NodeAggregateWithNodeWasCreated $event
     */
    final public function whenNodeAggregateWithNodeWasCreated(Event\NodeAggregateWithNodeWasCreated $event)
    {
        $this->transactional(function () use ($event) {
            $this->createNodeWithHierarchy(
                $event->getContentStreamIdentifier(),
                $event->getNodeAggregateIdentifier(),
                $event->getNodeTypeName(),
                $event->getNodeIdentifier(),
                $event->getParentNodeIdentifier(),
                $event->getDimensionSpacePoint(),
                $event->getVisibleDimensionSpacePoints(),
                $event->getPropertyDefaultValuesAndTypes(),
                $event->getNodeName()
            );
        });
    }

    /**
     * @param Event\NodeWasAddedToAggregate $event
     */
    final public function whenNodeWasAddedToAggregate(Event\NodeWasAddedToAggregate $event)
    {
        $this->transactional(function () use ($event) {
            $contentStreamIdentifier = $event->getContentStreamIdentifier();
            $nodeAggregateIdentifier = $event->getNodeAggregateIdentifier();

            $this->createNodeWithHierarchy(
                $contentStreamIdentifier,
                $nodeAggregateIdentifier,
                $event->getNodeTypeName(),
                $event->getNodeIdentifier(),
                $event->getParentNodeIdentifier(),
                $event->getDimensionSpacePoint(),
                $event->getVisibleDimensionSpacePoints(),
                $event->getPropertyDefaultValuesAndTypes(),
                $event->getNodeName()
            );
        });
    }

    private function createNodeWithHierarchy(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeTypeName $nodeTypeName,
        NodeIdentifier $nodeIdentifier,
        NodeIdentifier $parentNodeIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        DimensionSpacePointSet $visibleDimensionSpacePoints,
        array $propertyDefaultValuesAndTypes,
        NodeName $nodeName
    ) {
        $nodeRelationAnchorPoint = new NodeRelationAnchorPoint();
        $node = new Node(
            $nodeRelationAnchorPoint,
            $nodeIdentifier,
            $nodeAggregateIdentifier,
            $dimensionSpacePoint->jsonSerialize(),
            $dimensionSpacePoint->getHash(),
            array_map(function (ContentRepository\ValueObject\PropertyValue $propertyValue) {
                return $propertyValue->getValue();
            }, $propertyDefaultValuesAndTypes),
            $nodeTypeName
        );

        // reconnect parent relations
        $missingParentRelations = $visibleDimensionSpacePoints->getPoints();
        $existingParentRelations = $this->projectionContentGraph->findInboundHierarchyRelationsForNodeAggregate(
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $visibleDimensionSpacePoints
        );
        foreach ($existingParentRelations as $existingParentRelation) {
            $existingParentRelation->assignNewChildNode($nodeRelationAnchorPoint, $this->getDatabaseConnection());
            unset($missingParentRelations[$existingParentRelation->dimensionSpacePointHash]);
        }

        if (!empty($missingParentRelations)) {
            // add yet missing parent relations
            $designatedParentNode = $this->projectionContentGraph->getNode($parentNodeIdentifier, $contentStreamIdentifier);
            $parentIsRootNode = count($this->projectionContentGraph->findInboundHierarchyRelationsForNode($designatedParentNode->relationAnchorPoint, $contentStreamIdentifier)) === 0;
            foreach ($missingParentRelations as $dimensionSpacePoint) {
                if ($parentIsRootNode) {
                    $parentNode = $designatedParentNode;
                } else {
                    $parentNode = $this->projectionContentGraph->getNodeInAggregate(
                        $designatedParentNode->nodeAggregateIdentifier,
                        $contentStreamIdentifier,
                        $dimensionSpacePoint
                    );
                }

                $this->connectHierarchy(
                    $parentNode->relationAnchorPoint,
                    $nodeRelationAnchorPoint,
                    null,
                    $nodeName,
                    $contentStreamIdentifier,
                    new DimensionSpacePointSet([$dimensionSpacePoint])
                );
            }
        }

        // reconnect child relations
        $existingChildRelations = $this->projectionContentGraph->findOutboundHierarchyRelationsForNodeAggregate(
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $visibleDimensionSpacePoints
        );
        foreach ($existingChildRelations as $existingChildRelation) {
            $existingChildRelation->assignNewParentNode($nodeRelationAnchorPoint, $this->getDatabaseConnection());
        }

        $node->addToDatabase($this->getDatabaseConnection());
    }

    /**
     * @param NodeRelationAnchorPoint $parentNodeAnchorPoint
     * @param NodeRelationAnchorPoint $childNodeAnchorPoint
     * @param NodeRelationAnchorPoint|null $precedingSiblingNodeAnchorPoint
     * @param NodeName|null $relationName
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePointSet $dimensionSpacePointSet
     */
    protected function connectHierarchy(
        NodeRelationAnchorPoint $parentNodeAnchorPoint,
        NodeRelationAnchorPoint $childNodeAnchorPoint,
        ?NodeRelationAnchorPoint $precedingSiblingNodeAnchorPoint,
        NodeName $relationName = null,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePointSet $dimensionSpacePointSet
    ): void {
        foreach ($dimensionSpacePointSet->getPoints() as $dimensionSpacePoint) {
            $position = $this->getRelationPosition(
                $parentNodeAnchorPoint,
                $precedingSiblingNodeAnchorPoint,
                $contentStreamIdentifier,
                $dimensionSpacePoint
            );

            $hierarchyRelation = new HierarchyRelation(
                $parentNodeAnchorPoint,
                $childNodeAnchorPoint,
                $relationName,
                $contentStreamIdentifier,
                $dimensionSpacePoint,
                $dimensionSpacePoint->getHash(),
                $position
            );

            $hierarchyRelation->addToDatabase($this->getDatabaseConnection());
        }
    }

    /**
     * @param NodeRelationAnchorPoint $parentAnchorPoint
     * @param NodeRelationAnchorPoint|null $precedingSiblingAnchorPoint
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return int
     */
    protected function getRelationPosition(
        NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $precedingSiblingAnchorPoint,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): int {
        $position = $this->projectionContentGraph->getHierarchyRelationPosition($parentAnchorPoint, $precedingSiblingAnchorPoint, $contentStreamIdentifier, $dimensionSpacePoint);

        if ($position % 2 !== 0) {
            $position = $this->getRelationPositionAfterRecalculation($parentAnchorPoint, $precedingSiblingAnchorPoint, $contentStreamIdentifier, $dimensionSpacePoint);
        }

        return $position;
    }

    /**
     * @param NodeRelationAnchorPoint $parentAnchorPoint
     * @param NodeRelationAnchorPoint|null $precedingSiblingAnchorPoint
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return int
     */
    protected function getRelationPositionAfterRecalculation(
        NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $precedingSiblingAnchorPoint,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): int {
        $offset = 0;
        $position = 0;
        foreach ($this->projectionContentGraph->getOutboundHierarchyRelationsForNodeAndSubgraph($parentAnchorPoint, $contentStreamIdentifier, $dimensionSpacePoint) as $relation) {
            $relation->assignNewPosition($offset, $this->getDatabaseConnection());
            $offset += 128;
            if ($precedingSiblingAnchorPoint && $relation->childNodeAnchor === (string)$precedingSiblingAnchorPoint) {
                $position = $offset;
                $offset += 128;
            }
        }

        return $position;
    }

    /**
     * @param string $fallbackNodesIdentifierInGraph
     * @param string $newVariantNodesIdentifierInGraph
     * @param array $subgraphIdentifiers
     */
    protected function reconnectHierarchy(
        string $fallbackNodesIdentifierInGraph,
        string $newVariantNodesIdentifierInGraph,
        array $subgraphIdentifiers
    ): void {
        /*
        // TODO needs to be fixed
        $inboundRelations = $this->projectionContentGraph->findInboundHierarchyRelationsForNodeAndSubgraphs(
            $fallbackNodesIdentifierInGraph,
            $subgraphIdentifiers
        );
        $outboundRelations = $this->projectionContentGraph->findOutboundHierarchyRelationsForNodeAndSubgraphs(
            $fallbackNodesIdentifierInGraph,
            $subgraphIdentifiers
        );

        foreach ($inboundRelations as $inboundRelation) {
            $this->assignNewChildNodeToHierarchyRelation($inboundRelation, $newVariantNodesIdentifierInGraph);
        }
        foreach ($outboundRelations as $outboundRelation) {
            $this->assignNewParentNodeToHierarchyRelation($outboundRelation, $newVariantNodesIdentifierInGraph);
        }*/
    }

    public function whenContentStreamWasForked(ContentRepository\Context\ContentStream\Event\ContentStreamWasForked $event)
    {
        $this->transactional(function () use ($event) {
            $this->getDatabaseConnection()->executeUpdate('
                INSERT INTO neos_contentgraph_hierarchyrelation (
                  parentnodeanchor,
                  childnodeanchor,
                  `name`,
                  position,
                  dimensionspacepoint,
                  dimensionspacepointhash,
                  contentstreamidentifier
                )
                SELECT
                  h.parentnodeanchor,
                  h.childnodeanchor, 
                  h.name,
                  h.position,
                  h.dimensionspacepoint,
                  h.dimensionspacepointhash, 
                  "' . (string)$event->getContentStreamIdentifier() . '" AS contentstreamidentifier
                FROM
                    neos_contentgraph_hierarchyrelation h
                    WHERE h.contentstreamidentifier = :sourceContentStreamIdentifier
            ', [
                'sourceContentStreamIdentifier' => (string)$event->getSourceContentStreamIdentifier()
            ]);
        });
    }

    public function whenNodePropertyWasSet(NodePropertyWasSet $event)
    {
        $this->transactional(function () use ($event) {
            $this->updateNodeWithCopyOnWrite($event, function (Node $node) use ($event) {
                $node->properties[$event->getPropertyName()] = $event->getValue()->getValue();
            });
        });
    }

    public function whenNodeReferencesWereSet(NodeReferencesWereSet $event)
    {
        $this->transactional(function () use ($event) {
            $this->updateNodeWithCopyOnWrite($event, function (Node $node) use ($event) {});
            $nodeAnchorPoint = $this->projectionContentGraph->getAnchorPointForNodeAndContentStream($event->getNodeIdentifier(), $event->getContentStreamIdentifier());

            // remove old
            $this->getDatabaseConnection()->delete('neos_contentgraph_referencerelation', [
                'nodeanchorpoint' => $nodeAnchorPoint,
                'name' => $event->getPropertyName()
            ]);

            // set new
            foreach ($event->getDestinationtNodesAggregateIdentifiers() as $position => $destinationtNodeIdentifier) {
                $this->getDatabaseConnection()->insert('neos_contentgraph_referencerelation', [
                    'name' => $event->getPropertyName(),
                    'position' => $position,
                    'nodeanchorpoint' => $nodeAnchorPoint,
                    'destinationnodeaggregateidentifier' => $destinationtNodeIdentifier,
                ]);
            }
        });
    }

    public function whenNodeWasHidden(NodeWasHidden $event)
    {
        $this->transactional(function () use ($event) {
            $this->updateNodeWithCopyOnWrite($event, function (Node $node) use ($event) {
                $node->hidden = true;
            });
        });
    }

    public function whenNodeWasShown(NodeWasShown $event)
    {
        $this->transactional(function () use ($event) {
            $this->updateNodeWithCopyOnWrite($event, function (Node $node) {
                $node->hidden = false;
            });
        });
    }

    public function whenNodeInAggregateWasTranslated(Event\NodeInAggregateWasTranslated $event)
    {
        $this->transactional(function () use ($event) {
            $childNodeRelationAnchorPoint = new NodeRelationAnchorPoint();

            $sourceNode = $this->projectionContentGraph->getNodeByNodeIdentifierAndContentStream($event->getSourceNodeIdentifier(), $event->getContentStreamIdentifier());
            if ($sourceNode === null) {
                // TODO Log error
                return;
            }

            $translatedNode = new Node(
                $childNodeRelationAnchorPoint,
                $event->getDestinationNodeIdentifier(),
                $sourceNode->nodeAggregateIdentifier,
                $event->getDimensionSpacePoint()->jsonSerialize(),
                $event->getDimensionSpacePoint()->getHash(),
                $sourceNode->properties,
                $sourceNode->nodeTypeName
            );
            $parentNode = $this->projectionContentGraph->getNodeByNodeIdentifierAndContentStream($event->getDestinationParentNodeIdentifier(), $event->getContentStreamIdentifier());
            if ($parentNode === null) {
                // TODO Log error
                return;
            }

            $translatedNode->addToDatabase($this->getDatabaseConnection());
            $this->connectHierarchy(
                $parentNode->relationAnchorPoint,
                $translatedNode->relationAnchorPoint,
                // TODO: position on insert is still missing
                null,
                $sourceNode->nodeName,
                $event->getContentStreamIdentifier(),
                $event->getVisibleDimensionSpacePoints()
            );
        });
    }

    /**
     * @param callable $operations
     */
    protected function transactional(callable $operations): void
    {
        $this->getDatabaseConnection()->transactional($operations);
        $this->emitProjectionUpdated();
    }

    protected function updateNodeWithCopyOnWrite($event, callable $operations)
    {
        // TODO: do this copy on write on every modification op concerning nodes

        // TODO: does this always return a SINGLE anchor point??
        $anchorPointForNode = $this->projectionContentGraph->getAnchorPointForNodeAndContentStream($event->getNodeIdentifier(), $event->getContentStreamIdentifier());
        if ($anchorPointForNode === null) {
            // TODO Log error
            throw new \Exception(sprintf('anchor point for node identifier %s and stream %s not found', $event->getNodeIdentifier(), $event->getContentStreamIdentifier()), 1519681260000);
        }

        $contentStreamIdentifiers = $this->projectionContentGraph->getAllContentStreamIdentifiersAnchorPointIsContainedIn($anchorPointForNode);
        if (count($contentStreamIdentifiers) > 1) {
            // Copy on Write needed!
            // Copy on Write is a purely "Content Stream" related concept; thus we do not care about different DimensionSpacePoints here (but we copy all edges)

            // 1) fetch node, adjust properties, assign new Relation Anchor Point
            $copiedNode = $this->projectionContentGraph->getNodeByAnchorPoint($anchorPointForNode);
            $copiedNode->relationAnchorPoint = new NodeRelationAnchorPoint();
            $operations($copiedNode);
            $copiedNode->addToDatabase($this->getDatabaseConnection());

            // 2) reconnect all edges belonging to this content stream to the new "copied node". IMPORTANT: We need to reconnect
            // BOTH the incoming and outgoing edges.
            $this->getDatabaseConnection()->executeUpdate('
                UPDATE neos_contentgraph_hierarchyrelation h
                    SET 
                        -- if our (copied) node is the child, we update h.childNodeAnchor
                        h.childnodeanchor = IF(h.childnodeanchor = :originalNodeAnchor, :newNodeAnchor, h.childnodeanchor),
                        
                        -- if our (copied) node is the parent, we update h.parentNodeAnchor
                        h.parentnodeanchor = IF(h.parentnodeanchor = :originalNodeAnchor, :newNodeAnchor, h.parentnodeanchor)
                    WHERE
                      :originalNodeAnchor IN (h.childnodeanchor, h.parentnodeanchor)
                      AND h.contentstreamidentifier = :contentStreamIdentifier',
                [
                    'newNodeAnchor' => (string)$copiedNode->relationAnchorPoint,
                    'originalNodeAnchor' => (string)$anchorPointForNode,
                    'contentStreamIdentifier' => (string)$event->getContentStreamIdentifier()
                ]
            );
        } else {
            // No copy on write needed :)

            $node = $this->projectionContentGraph->getNodeByNodeIdentifierAndContentStream($event->getNodeIdentifier(), $event->getContentStreamIdentifier());
            if (!$node) {
                // TODO: ignore the ShowNode (if all other logic is correct)
                throw new \Exception("TODO NODE NOT FOUND");
            }

            $operations($node);
            $node->updateToDatabase($this->getDatabaseConnection());
        }
    }

    /**
     * @return Connection
     */
    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }

}
