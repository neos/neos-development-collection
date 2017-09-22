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
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
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

    final public function whenRootNodeWasCreated(Event\RootNodeWasCreated $event)
    {
        $nodeRelationAnchorPoint = new NodeRelationAnchorPoint();
        $node = Node::fromRootNodeWasCreated($nodeRelationAnchorPoint, $event);

        $this->transactional(function () use ($node) {
            $this->addNode($node);
        });
    }

    final public function whenNodeAggregateWithNodeWasCreated(Event\NodeAggregateWithNodeWasCreated $event)
    {
        $childNodeRelationAnchorPoint = new NodeRelationAnchorPoint();
        $childNode = Node::fromNodeAggregateWithNodeWasCreated($childNodeRelationAnchorPoint, $event);
        $parentNode = $this->projectionContentGraph->getNode($event->getParentNodeIdentifier(), $event->getContentStreamIdentifier(), $event->getDimensionSpacePoint());
        #$precedingSiblingNode = $this->getNode(null, $event->getContentStreamIdentifier(), $event->getDimensionSpacePoint());

        $this->transactional(function () use ($childNode, $parentNode, $event) {
            // generate relation anchor point in $node
            // fetch relation anchor point from parent
            // connect hierarchy (relation anchor point A, rel A Point B)
            $this->addNode($childNode);
            $this->connectHierarchy(
                new NodeRelationAnchorPoint($parentNode->relationAnchorPoint),
                new NodeRelationAnchorPoint($childNode->relationAnchorPoint),
                // TODO: position on insert is still missing
                null,
                $event->getNodeName(),
                $event->getContentStreamIdentifier(),
                $event->getVisibleDimensionSpacePoints()
            );
        });
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
     * @param Node $node
     */
    protected function addNode(Node $node): void
    {
        $this->getDatabaseConnection()->insert('neos_contentgraph_node', [
            'relationanchorpoint' => $node->relationAnchorPoint,
            'nodeaggregateidentifier' => $node->nodeAggregateIdentifier,
            'nodeidentifier' => $node->nodeIdentifier,
            'dimensionspacepoint' => json_encode($node->dimensionSpacePoint),
            'dimensionspacepointhash' => $node->dimensionSpacePointHash,
            'properties' => json_encode($node->properties),
            'nodetypename' => $node->nodeTypeName
        ]);
    }

    /**
     * @param NodeIdentifier $parentNodeIdentifier
     * @param NodeIdentifier $childNodeIdentifier
     * @param NodeIdentifier|null $precedingSiblingNodeIdentifier
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
                (string)$parentNodeAnchorPoint,
                (string)$childNodeAnchorPoint,
                (string)$relationName,
                (string)$contentStreamIdentifier,
                $dimensionSpacePoint->jsonSerialize(),
                $dimensionSpacePoint->getHash(),
                $position
            );

            // TODO: rewrite to $hierarchyRelation->saveToDatabase($db)
            $this->addHierarchyRelation($hierarchyRelation);
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
            if ($precedingSiblingAnchorPoint && $relation->getChildNodeAnchor() === (string)$precedingSiblingAnchorPoint) {
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
     */
    protected function addHierarchyRelation(HierarchyRelation $relation): void
    {
        $this->getDatabaseConnection()->insert('neos_contentgraph_hierarchyrelation', [
            'parentnodeanchor' => $relation->getParentNodeAnchor(),
            'childnodeanchor' => $relation->getChildNodeAnchor(),
            'name' => $relation->getName(),
            'contentstreamidentifier' => $relation->getContentStreamIdentifier(),
            'dimensionspacepoint' => json_encode($relation->getDimensionSpacePoint()),
            'dimensionspacepointhash' => $relation->getDimensionSpacePointHash(),
            'position' => $relation->getPosition()
        ]);
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
