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
use Neos\ContentGraph\DoctrineDbalAdapter\Infrastructure\Dto\HierarchyEdge;
use Neos\ContentGraph\DoctrineDbalAdapter\Infrastructure\Service\DbalClient;
use Neos\ContentGraph\Domain\Projection\AbstractGraphProjector;
use Neos\ContentGraph\Domain\Projection\Node;
use Neos\ContentRepository\Domain as ContentRepository;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\Flow\Annotations as Flow;

/**
 * The alternate reality-aware graph projector for the Doctrine backend
 *
 * @Flow\Scope("singleton")
 */
class GraphProjector extends AbstractGraphProjector
{
    /**
     * @Flow\Inject
     * @var ProjectionContentGraph
     */
    protected $contentGraph;

    /**
     * @Flow\Inject
     * @var DbalClient
     */
    protected $client;

    public function reset()
    {
        $this->getDatabaseConnection()->transactional(function () {
            $this->getDatabaseConnection()->executeQuery('TRUNCATE table neos_contentgraph_node');
            $this->getDatabaseConnection()->executeQuery('TRUNCATE table neos_contentgraph_hierarchyrelation');
        });
    }

    public function isEmpty(): bool
    {
        return $this->contentGraph->isEmpty();
    }

    protected function connectRelation(
        string $startNodesIdentifierInGraph,
        string $endNodesIdentifierInGraph,
        string $relationshipName,
        array $properties,
        array $subgraphIdentifiers
    ) {
        // TODO: Implement connectRelation() method.
    }


    /*
    public function whenPropertiesWereUpdated(Event\PropertiesWereUpdated $event)
    {
        $node = $this->nodeFinder->findOneByIdentifierInGraph($event->getVariantIdentifier());
        $node->properties = Arrays::arrayMergeRecursiveOverrule($node->properties, $event->getProperties());
        $this->update($node);

        $this->projectionPersistenceManager->persistAll();
    }*/

    /*
    public function whenNodeWasMoved(Event\NodeWasMoved $event)
    {
        $subgraphIdentifier = $this->extractSubgraphIdentifierFromEvent($event);
        $affectedSubgraphIdentifiers = $event->getStrategy() === Event\NodeWasMoved::STRATEGY_CASCADE_TO_ALL_VARIANTS
            ? $this->fallbackGraphService->determineAffectedVariantSubgraphIdentifiers($subgraphIdentifier)
            : $this->fallbackGraphService->determineConnectedSubgraphIdentifiers($subgraphIdentifier);

        foreach ($this->hierarchyEdgeFinder->findInboundByNodeAndSubgraphs($event->getVariantIdentifier(),
            $affectedSubgraphIdentifiers) as $variantEdge) {
            if ($event->getNewParentVariantIdentifier()) {
                $variantEdge->parentNodesIdentifierInGraph = $event->getNewParentVariantIdentifier();
            }
            // @todo: handle new older sibling

            $this->update($variantEdge);
        }

        $this->projectionPersistenceManager->persistAll();
    }*/

    /*
    public function whenNodeWasRemoved(Event\NodeWasRemoved $event)
    {
        $node = $this->nodeFinder->findOneByIdentifierInGraph($event->getVariantIdentifier());
        $subgraphIdentifier = $this->extractSubgraphIdentifierFromEvent($event);

        if ($node->subgraphIdentifier === $subgraphIdentifier) {
            foreach ($this->hierarchyEdgeFinder->findByChildNodeIdentifierInGraph($event->getVariantIdentifier()) as $inboundEdge) {
                $this->remove($inboundEdge);
            }
            $this->remove($node);
        } else {
            $affectedSubgraphIdentifiers = $this->fallbackGraphService->determineAffectedVariantSubgraphIdentifiers($subgraphIdentifier);
            foreach ($this->hierarchyEdgeFinder->findInboundByNodeAndSubgraphs($event->getVariantIdentifier(),
                $affectedSubgraphIdentifiers) as $affectedInboundEdge) {
                $this->remove($affectedInboundEdge);
            }
        }

        // @todo handle reference edges

        $this->projectionPersistenceManager->persistAll();
    }*/

    /*
    public function whenNodeReferenceWasAdded(Event\NodeReferenceWasAdded $event)
    {
        $referencingNode = $this->nodeFinder->findOneByIdentifierInGraph($event->getReferencingNodeIdentifier());
        $referencedNode = $this->nodeFinder->findOneByIdentifierInGraph($event->getReferencedNodeIdentifier());

        $affectedSubgraphIdentifiers = [];
        // Which variant reference edges are created alongside is determined by what subgraph this node belongs to
        // @todo define a more fitting method in a service for this
        $inboundHierarchyEdges = $this->hierarchyEdgeFinder->findByChildNodeIdentifierInGraph($event->getReferencingNodeIdentifier());

        foreach ($inboundHierarchyEdges as $hierarchyEdge) {
            $referencedNodeVariant = $this->nodeFinder->findInSubgraphByIdentifierInSubgraph($referencingNode->identifierInSubgraph,
                $hierarchyEdge->subgraphIdentifier);
            if ($referencedNodeVariant) {
                $referenceEdge = new ReferenceEdge();
                $referenceEdge->connect($referencingNode, $referencedNode, $hierarchyEdge->name,
                    $event->getReferenceName());
                // @todo fetch position among siblings
                // @todo implement auto-triggering of position recalculation
            }
        }
    }*/


    protected function getNode(ContentRepository\ValueObject\NodeAggregateIdentifier $nodeIdentifier, ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint): Node
    {
        return $this->contentGraph->getNode($nodeIdentifier, $contentStreamIdentifier, $dimensionSpacePoint);
    }

    protected function addNode(Node $node)
    {
        $this->getDatabaseConnection()->insert('neos_contentgraph_node', [
            'nodeaggregateidentifier' => $node->nodeAggregateIdentifier,
            'nodeidentifier' => $node->nodeIdentifier,
            'contentstreamidentifier' => $node->contentStreamIdentifier,
            'dimensionspacepoint' => json_encode($node->dimensionSpacePoint),
            'dimensionspacepointhash' => $node->dimensionSpacePointHash,
            'properties' => json_encode($node->properties),
            'nodetypename' => $node->nodeTypeName
        ]);
    }

    protected function connectHierarchy(
        NodeIdentifier $parentNodeIdentifier,
        NodeIdentifier $childNodeIdentifier,
        NodeIdentifier $preceedingSiblingNodeIdentifier = null,
        NodeName $edgeName = null,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePointSet $dimensionSpacePointSet
    ) {
        foreach ($dimensionSpacePointSet->getPoints() as $dimensionSpacePoint) {
            $position = $this->getEdgePosition($parentNodeIdentifier, $preceedingSiblingNodeIdentifier, $contentStreamIdentifier, $dimensionSpacePoint);
            $hierarchyEdge = new HierarchyEdge(
                (string)$parentNodeIdentifier,
                (string)$childNodeIdentifier,
                (string)$edgeName,
                (string)$contentStreamIdentifier,
                $dimensionSpacePoint->jsonSerialize(),
                $dimensionSpacePoint->getHash(),
                $position
            );

            $this->addHierarchyEdge($hierarchyEdge);
        }
    }

    protected function getEdgePosition(NodeIdentifier $parentIdentifier, NodeIdentifier $precedingSiblingIdentifier = null, ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint): int
    {
        $position = $this->contentGraph->getEdgePosition($parentIdentifier, $precedingSiblingIdentifier, $contentStreamIdentifier, $dimensionSpacePoint);

        if ($position % 2 !== 0) {
            $position = $this->getEdgePositionAfterRecalculation($parentIdentifier, $precedingSiblingIdentifier, $contentStreamIdentifier, $dimensionSpacePoint);
        }

        return $position;
    }

    protected function getEdgePositionAfterRecalculation(NodeIdentifier $parentIdentifier, NodeIdentifier $precedingSiblingIdentifier, ContentStreamIdentifier $contentStreamIdentifier, DimensionSpacePoint $dimensionSpacePoint): int
    {
        $offset = 0;
        $position = 0;
        foreach ($this->contentGraph->getOutboundHierarchyEdgesForNodeAndSubgraph($parentIdentifier, $contentStreamIdentifier, $dimensionSpacePoint) as $edge) {
            $this->assignNewPositionToHierarchyEdge($edge, $offset);
            $offset += 128;
            if ($edge->getChildNodeIdentifier() === (string)$precedingSiblingIdentifier) {
                $position = $offset;
                $offset += 128;
            }
        }

        return $position;
    }

    // TODO needs to be fixed
    protected function reconnectHierarchy(
        string $fallbackNodesIdentifierInGraph,
        string $newVariantNodesIdentifierInGraph,
        array $subgraphIdentifiers
    ) {
        $inboundEdges = $this->contentGraph->findInboundHierarchyEdgesForNodeAndSubgraphs(
            $fallbackNodesIdentifierInGraph,
            $subgraphIdentifiers
        );
        $outboundEdges = $this->contentGraph->findOutboundHierarchyEdgesForNodeAndSubgraphs(
            $fallbackNodesIdentifierInGraph,
            $subgraphIdentifiers
        );

        foreach ($inboundEdges as $inboundEdge) {
            $this->assignNewChildNodeToHierarchyEdge($inboundEdge, $newVariantNodesIdentifierInGraph);
        }
        foreach ($outboundEdges as $outboundEdge) {
            $this->assignNewParentNodeToHierarchyEdge($outboundEdge, $newVariantNodesIdentifierInGraph);
        }
    }

    protected function addHierarchyEdge(HierarchyEdge $edge)
    {
        $this->getDatabaseConnection()->insert('neos_contentgraph_hierarchyrelation', [
            'parentNodeIdentifier' => $edge->getParentNodeIdentifier(),
            'childNodeIdentifier' => $edge->getChildNodeIdentifier(),
            'name' => $edge->getEdgeName(),
            'contentstreamidentifier' => $edge->getContentStreamIdentifier(),
            'dimensionspacepoint' => json_encode($edge->getDimensionSpacePoint()),
            'dimensionspacepointhash' => $edge->getDimensionSpacePointHash(),
            'position' => $edge->getPosition()
        ]);
    }

    protected function assignNewPositionToHierarchyEdge(HierarchyEdge $edge, int $position)
    {
        $this->getDatabaseConnection()->update(
            'neos_contentgraph_hierarchyrelation',
            [
                'position' => $position
            ],
            $edge->getDatabaseIdentifier()
        );
    }

    protected function assignNewChildNodeToHierarchyEdge(HierarchyEdge $edge, string $childNodeIdentifierInGraph)
    {
        $this->getDatabaseConnection()->update(
            'neos_contentgraph_hierarchyrelation',
            [
                'childnodesidentifieringraph' => $childNodeIdentifierInGraph,
            ],
            $edge->getDatabaseIdentifier()
        );
    }

    protected function assignNewParentNodeToHierarchyEdge(HierarchyEdge $edge, string $parentNodeIdentifierInGraph)
    {
        $this->getDatabaseConnection()->update(
            'neos_contentgraph_hierarchyrelation',
            [
                'parentnodesidentifieringraph' => $parentNodeIdentifierInGraph,
            ],
            $edge->getDatabaseIdentifier()
        );
    }


    protected function transactional(callable $operations)
    {
        $this->getDatabaseConnection()->transactional($operations);
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }
}
