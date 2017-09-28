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
use Neos\ContentGraph\DoctrineDbalAdapter\Infrastructure\Service\DbalClient;
use Neos\ContentRepository\Domain\Context\Node\Event;
use Neos\ContentRepository\Domain as ContentRepository;
use Neos\ContentRepository\Domain\Context\Node\Event\NodePropertyWasSet;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifierAndDimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
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

    public function reset(): void
    {
        $this->getDatabaseConnection()->transactional(function () {
            $this->getDatabaseConnection()->executeQuery('TRUNCATE table neos_contentgraph_node');
            $this->getDatabaseConnection()->executeQuery('TRUNCATE table neos_contentgraph_hierarchyrelation');
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
            new NodeTypeName('Neos.ContentRepository:Root')
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

            $this->reconnectNodeVisibilities(
                $contentStreamIdentifier,
                $nodeAggregateIdentifier,
                $event->getNodeVisibilityChanges()
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
        $childNodeRelationAnchorPoint = new NodeRelationAnchorPoint();
        $childNode = new Node(
            $childNodeRelationAnchorPoint,
            $nodeIdentifier,
            $nodeAggregateIdentifier,
            $dimensionSpacePoint->jsonSerialize(),
            $dimensionSpacePoint->getHash(),
            array_map(function (ContentRepository\ValueObject\PropertyValue $propertyValue) {
                return $propertyValue->getValue();
            }, $propertyDefaultValuesAndTypes),
            $nodeTypeName
        );
        $parentNode = $this->projectionContentGraph->getNode($parentNodeIdentifier, $contentStreamIdentifier, $dimensionSpacePoint);
        if ($parentNode === null) {
            // TODO Log error
            return;
        }

        #$precedingSiblingNode = $this->getNode(null, $event->getContentStreamIdentifier(), $event->getDimensionSpacePoint());
        $precedingSiblingNode = null;

        // generate relation anchor point in $node
        // fetch relation anchor point from parent
        // connect hierarchy (relation anchor point A, rel A Point B)
        $childNode->addToDatabase($this->getDatabaseConnection());
        $this->connectHierarchy(
            $parentNode->relationAnchorPoint,
            $childNode->relationAnchorPoint,
            // TODO: position on insert is still missing
            null,
            $nodeName,
            $contentStreamIdentifier,
            $visibleDimensionSpacePoints
        );
    }

    /**
     * Reconnect nodes in an aggregate based on the given node visibility changes
     *
     * Must be called in a transaction.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param NodeIdentifierAndDimensionSpacePointSet[] $nodeVisibilityChanges
     */
    private function reconnectNodeVisibilities(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        array $nodeVisibilityChanges
    ) {

        // Iterate over all nodes in $nodeVisibilityChanges
        foreach ($nodeVisibilityChanges as $nodeIdentifierAndDimensionSpacePointSet) {
            $node = $this->projectionContentGraph->getNodeByNodeIdentifierAndContentStream($nodeIdentifierAndDimensionSpacePointSet->getNodeIdentifier(), $contentStreamIdentifier);

            $originRelation = null;
            $relations = $this->projectionContentGraph->findInboundHierarchyRelationsForNode($node->relationAnchorPoint,
                $contentStreamIdentifier);
            // Remove all other connections
            foreach ($relations as $relation) {
                if ($relation->dimensionSpacePointHash !== $node->dimensionSpacePointHash) {
                    // TODO It would be more efficient to keep relations that should be added
                    $relation->removeFromDatabase($this->getDatabaseConnection());
                } else {
                    // Get connection for origin dimension space point
                    $originRelation = $relation;
                }
            }

            if ($originRelation === null) {
                // TODO Log error
                break;
            }

            // Create connection for each point in $nodeVisibilityChanges except the origin
            $pointsToAdd = $nodeIdentifierAndDimensionSpacePointSet->getDimensionSpacePointSet()->getPoints();
            foreach ($pointsToAdd as $point) {
                if ($point->getHash() !== $node->dimensionSpacePointHash) {
                    $relation = new HierarchyRelation(
                        $originRelation->parentNodeAnchor,
                        $originRelation->childNodeAnchor,
                        $originRelation->name,
                        $contentStreamIdentifier,
                        $point,
                        $point->getHash(),
                        // TODO Check if it's okay to copy this from the origin relation?
                        $originRelation->position
                    );
                    $relation->addToDatabase($this->getDatabaseConnection());
                }
            }
        }
    }

    /**
     * @param string $startNodesIdentifierInGraph
     * @param string $endNodesIdentifierInGraph
     * @param string $relationshipName
     * @param array $properties
     * @param array $subgraphIdentifiers
     */
    protected function connectRelation(
        string $startNodesIdentifierInGraph,
        string $endNodesIdentifierInGraph,
        string $relationshipName,
        array $properties,
        array $subgraphIdentifiers
    ): void {
        // TODO: Implement connectRelation() method.
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
            $this->assignNewPositionToHierarchyRelation($relation, $offset);
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

    /**
     * @param HierarchyRelation $relation
     * @param int $position
     */
    protected function assignNewPositionToHierarchyRelation(HierarchyRelation $relation, int $position): void
    {
        $this->getDatabaseConnection()->update(
            'neos_contentgraph_hierarchyrelation',
            [
                'position' => $position
            ],
            $relation->getDatabaseIdentifier()
        );
    }

    /*
    protected function assignNewChildNodeToHierarchyRelation(HierarchyRelation $relation, string $childNodeIdentifierInGraph)
    {
        $this->getDatabaseConnection()->update(
            'neos_contentgraph_hierarchyrelation',
            [
                'childnodesidentifieringraph' => $childNodeIdentifierInGraph,
            ],
            $relation->getDatabaseIdentifier()
        );
    }

    protected function assignNewParentNodeToHierarchyRelation(HierarchyRelation $relation, string $parentNodeIdentifierInGraph)
    {
        $this->getDatabaseConnection()->update(
            'neos_contentgraph_hierarchyrelation',
            [
                'parentnodesidentifieringraph' => $parentNodeIdentifierInGraph,
            ],
            $relation->getDatabaseIdentifier()
        );
    }*/

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
            // TODO: do this copy on write on every modification op concerning nodes

            // TODO: does this always return a SINGLE anchor point??
            $anchorPointForNode = $this->projectionContentGraph->getAnchorPointForNodeAndContentStream($event->getNodeIdentifier(), $event->getContentStreamIdentifier());
            if ($anchorPointForNode === null) {
                // TODO Log error
                throw new \Exception(sprintf('anchro point for node identifier %s and stream %s not found', $event->getNodeIdentifier(), $event->getContentStreamIdentifier()), 1506085300325);
            }

            $contentStreamIdentifiers = $this->projectionContentGraph->getAllContentStreamIdentifiersAnchorPointIsContainedIn($anchorPointForNode);
            if (count($contentStreamIdentifiers) > 1) {
                // Copy on Write needed!
                // Copy on Write is a purely "Content Stream" related concept; thus we do not care about different DimensionSpacePoints here (but we copy all edges)

                // 1) fetch node, adjust properties, assign new Relation Anchor Point
                $copiedNode = $this->projectionContentGraph->getNodeByAnchorPoint($anchorPointForNode);
                $copiedNode->properties[$event->getPropertyName()] = $event->getValue()->getValue();
                $copiedNode->relationAnchorPoint = new NodeRelationAnchorPoint();
                $copiedNode->addToDatabase($this->getDatabaseConnection());

                // 2) reconnect all edges belonging to this content stream to the new "copied node"
                $this->getDatabaseConnection()->executeUpdate('
                UPDATE neos_contentgraph_hierarchyrelation h
                    SET h.childnodeanchor = :newChildNodeAnchor
                    WHERE
                      h.childnodeanchor = :originalChildNodeAnchor
                      AND h.contentstreamidentifier = :contentStreamIdentifier',
                    [
                        'newChildNodeAnchor' => (string)$copiedNode->relationAnchorPoint,
                        'originalChildNodeAnchor' => (string)$anchorPointForNode,
                        'contentStreamIdentifier' => (string)$event->getContentStreamIdentifier()
                    ]
                );
            } else {
                // No copy on write needed :)

                $node = $this->projectionContentGraph->getNodeByNodeIdentifierAndContentStream($event->getNodeIdentifier(), $event->getContentStreamIdentifier());
                if (!$node) {
                    // TODO: ignore the SetProperty (if all other logic is correct)
                    throw new \Exception("TODO NODE NOT FOUND");
                }
                $nodeProperties = $node->properties;
                $nodeProperties[$event->getPropertyName()] = $event->getValue()->getValue();

                $this->getDatabaseConnection()->executeUpdate('
                    UPDATE neos_contentgraph_node n
                    SET n.properties = :properties
                    WHERE n.relationanchorpoint = :relationAnchorPoint
                ', [
                    'properties' => json_encode($nodeProperties),
                    'relationAnchorPoint' => (string)$node->relationAnchorPoint
                ]);

            }

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
    }

    /**
     * @return Connection
     */
    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }

}
