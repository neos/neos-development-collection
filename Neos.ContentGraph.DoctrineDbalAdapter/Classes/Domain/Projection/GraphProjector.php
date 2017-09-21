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
use Neos\ContentGraph\Infrastructure\Dto\Node;
use Neos\ContentRepository\Domain as ContentRepository;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\SubgraphIdentifier;
use Neos\ContentRepository\Domain\ValueObject\SubgraphIdentifierSet;
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


    protected function getNode(ContentRepository\ValueObject\NodeAggregateIdentifier $nodeIdentifier, SubgraphIdentifier $subgraphIdentifier): Node
    {
        return $this->contentGraph->getNode((string) $nodeIdentifier, $subgraphIdentifier->getHash());
    }

    protected function addNode(Node $node)
    {
        $this->getDatabaseConnection()->insert('neos_contentgraph_node', [
            'nodeaggregateidentifier' => $node->nodeAggregateIdentifier,
            'nodeidentifier' => $node->nodeIdentifier,
            'subgraphidentifier' => json_encode($node->subgraphIdentifier),
            'subgraphidentityhash' => $node->subgraphIdentityHash,
            'properties' => json_encode($node->properties),
            'nodetypename' => $node->nodeTypeName
        ]);
    }

    protected function connectHierarchy(
        NodeIdentifier $parentNodeIdentifier,
        NodeIdentifier $childNodeIdentifier,
        NodeIdentifier $preceedingSiblingNodeIdentifier = null,
        NodeName $edgeName = null,
        SubgraphIdentifierSet $subgraphIdentifierSet
    ) {
        foreach ($subgraphIdentifierSet->getSubgraphIdentifiers() as $subgraphIdentifier) {
            $position = $this->getEdgePosition($parentNodeIdentifier, $preceedingSiblingNodeIdentifier, $subgraphIdentifier);
            $hierarchyEdge = new HierarchyEdge(
                (string)$parentNodeIdentifier,
                (string)$childNodeIdentifier,
                (string)$edgeName,
                $subgraphIdentifier->getHash(),
                $subgraphIdentifier->jsonSerialize(),
                $position
            );

            $this->addHierarchyEdge($hierarchyEdge);
        }
    }

    protected function getEdgePosition(NodeIdentifier $parentIdentifier, NodeIdentifier $precedingSiblingIdentifier = null, SubgraphIdentifier $subgraphIdentifier): int
    {
        $position = $this->contentGraph->getEdgePosition($parentIdentifier, $precedingSiblingIdentifier, $subgraphIdentifier);

        if ($position % 2 !== 0) {
            $position = $this->getEdgePositionAfterRecalculation($parentIdentifier, $precedingSiblingIdentifier, $subgraphIdentifier);
        }

        return $position;
    }

    protected function getEdgePositionAfterRecalculation(NodeIdentifier $parentIdentifier, NodeIdentifier $precedingSiblingIdentifier, SubgraphIdentifier $subgraphIdentifier): int
    {
        $offset = 0;
        $position = 0;
        foreach ($this->contentGraph->getOutboundHierarchyEdgesForNodeAndSubgraph($parentIdentifier, $subgraphIdentifier) as $edge) {
            $this->assignNewPositionToHierarchyEdge($edge, $offset);
            $offset += 128;
            if ($edge->getChildNodeIdentifier() === (string)$precedingSiblingIdentifier) {
                $position = $offset;
                $offset += 128;
            }
        }

        return $position;
    }

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
            'subgraphidentityhash' => $edge->getSubgraphIdentityHash(),
            'subgraphidentifier' => json_encode($edge->getSubgraphIdentifier()),
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
