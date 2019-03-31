<?php
declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\EventSourcedContentRepository\Domain;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeAggregate;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;

/**
 * The Doctrine DBAL adapter content graph
 *
 * To be used as a read-only source of nodes
 *
 * @Flow\Scope("singleton")
 * @api
 */
final class ContentGraph implements ContentGraphInterface
{
    /**
     * @Flow\Inject
     * @var DbalClient
     */
    protected $client;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @var array|ContentSubgraphInterface[]
     */
    protected $subgraphs;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return ContentSubgraphInterface|null
     */
    final public function getSubgraphByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        Domain\Context\Parameters\VisibilityConstraints $visibilityConstraints
    ): ?ContentSubgraphInterface {
        $index = (string)$contentStreamIdentifier . '-' . $dimensionSpacePoint->getHash() . '-' . $visibilityConstraints->getHash();
        if (!isset($this->subgraphs[$index])) {
            $this->subgraphs[$index] = new ContentSubgraph($contentStreamIdentifier, $dimensionSpacePoint, $visibilityConstraints);
        }

        return $this->subgraphs[$index];
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePoint $originDimensionSpacePoint
     * @return NodeInterface|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findNodeByIdentifiers(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePoint $originDimensionSpacePoint
    ): ?NodeInterface {
        $connection = $this->client->getConnection();

        // @todo remove fetching additional dimension space point once the matter is resolved
        // HINT: we check the ContentStreamIdentifier on the EDGE; as this is where we actually find out whether the node exists in the content stream
        $nodeRow = $connection->executeQuery(
            'SELECT n.*, h.contentstreamidentifier, h.name, n.origindimensionspacepoint, n.origindimensionspacepoint AS dimensionspacepoint FROM neos_contentgraph_node n
                  INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                  WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
                  AND n.origindimensionspacepointhash = :originDimensionSpacePointHash
                  AND h.contentstreamidentifier = :contentStreamIdentifier',
            [
                'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
                'originDimensionSpacePointHash' => $originDimensionSpacePoint->getHash(),
                'contentStreamIdentifier' => (string)$contentStreamIdentifier
            ]
        )->fetch();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode($nodeRow) : null;
    }

    /**
     * Find all nodes of a node aggregate by node aggregate identifier and content stream identifier
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePointSet $dimensionSpacePointSet
     * @return array<NodeInterface>|NodeInterface[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findNodesByNodeAggregateIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePointSet $dimensionSpacePointSet = null
    ): array {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*, h.name, h.contentstreamidentifier, h.dimensionspacepoint FROM neos_contentgraph_node n
 INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
 WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
 AND h.contentstreamidentifier = :contentStreamIdentifier';
        $parameters = [
            'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
            'contentStreamIdentifier' => (string)$contentStreamIdentifier
        ];
        $types = [];
        if ($dimensionSpacePointSet) {
            $query .= ' AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)';
            $parameters['dimensionSpacePointHashes'] = $dimensionSpacePointSet->getPointHashes();
            $types['dimensionSpacePointHashes'] = Connection::PARAM_STR_ARRAY;
        }

        $result = [];
        foreach ($connection->executeQuery(
            $query,
            $parameters,
            $types
        )->fetchAll() as $nodeRow) {
            $result[] = $this->nodeFactory->mapNodeRowToNode($nodeRow, null);
        }

        return $result;
    }

    /**
     * @param NodeTypeName $nodeTypeName
     * @return NodeInterface|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function findRootNodeByType(NodeTypeName $nodeTypeName): ?NodeInterface
    {
        $connection = $this->client->getConnection();

        // TODO: this code might still be broken somehow; because we are not in a DimensionSpacePoint / ContentStreamIdentifier here!
        $nodeRow = $connection->executeQuery(
            'SELECT n.*, h.contentstreamidentifier, h.name, h.dimensionspacepoint FROM neos_contentgraph_node n
                    INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                  WHERE n.nodetypename = :nodeTypeName',
            [
                'nodeTypeName' => (string)$nodeTypeName,
            ]
        )->fetch();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode($nodeRow, null) : null;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return NodeAggregate|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function findNodeAggregateByIdentifier(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): ?NodeAggregate {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*, h.name, h.contentstreamidentifier, h.dimensionspacepoint FROM neos_contentgraph_node n
                      JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                      WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
                      AND h.contentstreamidentifier = :contentStreamIdentifier';
        $parameters = [
            'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
            'contentStreamIdentifier' => (string)$contentStreamIdentifier
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAll();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate($nodeRows);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return array|NodeAggregate[]
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function findParentNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): array {
        $connection = $this->client->getConnection();

        $query = 'SELECT p.*, ph.name, ph.contentstreamidentifier, ph.dimensionspacepoint FROM neos_contentgraph_node p
                      JOIN neos_contentgraph_hierarchyrelation ph ON ph.childnodeanchor = p.relationanchorpoint
                      JOIN neos_contentgraph_hierarchyrelation ch ON ch.parentnodeanchor = p.relationanchorpoint
                      JOIN neos_contentgraph_node c ON ch.childnodeanchor = c.relationanchorpoint
                      WHERE c.nodeaggregateidentifier = :nodeAggregateIdentifier
                      AND ph.contentstreamidentifier = :contentStreamIdentifier
                      AND ch.contentstreamidentifier = :contentStreamIdentifier';
        $parameters = [
            'nodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
            'contentStreamIdentifier' => (string)$contentStreamIdentifier
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAll();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates($nodeRows);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $childNodeAggregateIdentifier
     * @param DimensionSpacePoint $childOriginDimensionSpacePoint
     * @return NodeAggregate|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier,
        DimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate {
        $connection = $this->client->getConnection();

        $query = 'SELECT n.*, h.name, h.contentstreamidentifier, h.dimensionspacepoint FROM neos_contentgraph_node n
                      JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                      WHERE n.nodeaggregateidentifier = (
                          SELECT p.nodeaggregateidentifier FROM neos_contentgraph_node p
                          INNER JOIN neos_contentgraph_hierarchyrelation ch ON ch.parentnodeanchor = p.relationanchorpoint
                          INNER JOIN neos_contentgraph_node c ON ch.childnodeanchor = c.relationanchorpoint
                          WHERE ch.contentstreamidentifier = :contentStreamIdentifier
                          AND ch.dimensionspacepointhash = :childOriginDimensionSpacePointHash
                          AND c.nodeaggregateidentifier = :childNodeAggregateIdentifier
                          AND c.origindimensionspacepointhash = :childOriginDimensionSpacePointHash
                      )
                      AND h.contentstreamidentifier = :contentStreamIdentifier
            ';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'childNodeAggregateIdentifier' => (string)$childNodeAggregateIdentifier,
            'childOriginDimensionSpacePointHash' => $childOriginDimensionSpacePoint->getHash(),
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAll();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate($nodeRows);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @return array|NodeAggregate[]
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function findChildNodeAggregates(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier
    ): array {
        $connection = $this->client->getConnection();

        $query = 'SELECT c.*, ch.name, ch.contentstreamidentifier, ch.dimensionspacepoint FROM neos_contentgraph_node p
                      JOIN neos_contentgraph_hierarchyrelation ph ON ph.childnodeanchor = p.relationanchorpoint
                      JOIN neos_contentgraph_hierarchyrelation ch ON ch.parentnodeanchor = p.relationanchorpoint
                      JOIN neos_contentgraph_node c ON ch.childnodeanchor = c.relationanchorpoint
                      WHERE p.nodeaggregateidentifier = :nodeAggregateIdentifier
                      AND ph.contentstreamidentifier = :contentStreamIdentifier
                      AND ch.contentstreamidentifier = :contentStreamIdentifier';
        $parameters = [
            'nodeAggregateIdentifier' => (string) $parentNodeAggregateIdentifier,
            'contentStreamIdentifier' => (string) $contentStreamIdentifier
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAll();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates($nodeRows);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeName $name
     * @return NodeAggregate|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws Domain\Context\NodeAggregate\ChildNodeAggregateIsAmbiguous
     * @throws \Exception
     */
    public function findChildNodeAggregateByName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $name
    ): ?NodeAggregate {
        $connection = $this->client->getConnection();

        $query = 'SELECT c.*, ch.name, ch.contentstreamidentifier, ch.dimensionspacepoint FROM neos_contentgraph_node p
                      JOIN neos_contentgraph_hierarchyrelation ph ON ph.childnodeanchor = p.relationanchorpoint
                      JOIN neos_contentgraph_hierarchyrelation ch ON ch.parentnodeanchor = p.relationanchorpoint
                      JOIN neos_contentgraph_node c ON ch.childnodeanchor = c.relationanchorpoint
                      WHERE p.nodeaggregateidentifier = :parentNodeAggregateIdentifier
                      AND ph.contentstreamidentifier = :contentStreamIdentifier
                      AND ch.name = :relationName
                      AND ch.contentstreamidentifier = :contentStreamIdentifier';

        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'parentNodeAggregateIdentifier' => (string)$parentNodeAggregateIdentifier,
            'relationName' => (string)$name
        ];

        $nodeRows = $connection->executeQuery($query, $parameters)->fetchAll();
        if (empty($nodeRows)) {
            return null;
        }

        try {
            return $this->nodeFactory->mapNodeRowsToNodeAggregate($nodeRows);
        } catch (Domain\Context\NodeAggregate\NodeAggregateIsAmbiguous $e) {
            throw new Domain\Context\NodeAggregate\ChildNodeAggregateIsAmbiguous('Child node aggregate with name "' . $name . '" for parent node aggregate "' . $parentNodeAggregateIdentifier . '" is ambiguous.', 1552691409, $e);
        }
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeName $nodeName
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param DimensionSpacePoint $parentNodeDimensionSpacePoint
     * @param DimensionSpacePointSet $dimensionSpacePointsToCheck
     * @return DimensionSpacePointSet
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDimensionSpacePointsOccupiedByChildNodeName(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeName $nodeName,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        DimensionSpacePoint $parentNodeDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ) {
        $connection = $this->client->getConnection();

        $query = 'SELECT h.dimensionspacepoint, h.dimensionspacepointhash FROM neos_contentgraph_hierarchyrelation h
                      INNER JOIN neos_contentgraph_node n ON h.parentnodeanchor = n.relationanchorpoint
                      INNER JOIN neos_contentgraph_hierarchyrelation ph ON ph.childnodeanchor = n.relationanchorpoint
                      WHERE n.nodeaggregateidentifier = :parentNodeAggregateIdentifier
                      AND n.origindimensionspacepointhash = :parentNodeDimensionSpacePoint
                      AND ph.contentstreamidentifier = :contentStreamIdentifier
                      AND h.contentstreamidentifier = :contentStreamIdentifier
                      AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)
                      AND h.name = :nodeName';
        $parameters = [
            'parentNodeAggregateIdentifier' => (string)$parentNodeAggregateIdentifier,
            'parentNodeDimensionSpacePoint' => $parentNodeDimensionSpacePoint->getHash(),
            'contentStreamIdentifier' => (string) $contentStreamIdentifier,
            'dimensionSpacePointHashes' => $dimensionSpacePointsToCheck->getPointHashes(),
            'nodeName' => (string) $nodeName
        ];
        $types = [
            'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
        ];
        $dimensionSpacePoints = [];
        foreach ($connection->executeQuery($query, $parameters, $types)->fetchAll() as $hierarchyRelationData) {
            $dimensionSpacePoints[$hierarchyRelationData['dimensionspacepointhash']] = new DimensionSpacePoint(json_decode($hierarchyRelationData['dimensionspacepoint'], true));
        }

        return new DimensionSpacePointSet($dimensionSpacePoints);
    }

    /**
     * @param NodeInterface $node
     * @return DimensionSpacePointSet
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findVisibleDimensionSpacePointsOfNode(NodeInterface $node): DimensionSpacePointSet
    {
        $connection = $this->client->getConnection();

        $query = 'SELECT h.dimensionspacepoint, h.dimensionspacepointhash FROM neos_contentgraph_node n
                      INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                      WHERE n.nodeidentifier = :nodeIdentifier
                      AND h.contentstreamidentifier = :contentStreamIdentifier';
        $parameters = [
            'nodeIdentifier' => (string)$node->getNodeIdentifier(),
            'contentStreamIdentifier' => (string)$node->getContentStreamIdentifier()
        ];
        $dimensionSpacePoints = [];
        foreach ($connection->executeQuery($query, $parameters)->fetchAll() as $hierarchyRelationData) {
            $dimensionSpacePoints[$hierarchyRelationData['dimensionspacepointhash']] = new DimensionSpacePoint(json_decode($hierarchyRelationData['dimensionspacepoint'], true));
        }

        return new DimensionSpacePointSet($dimensionSpacePoints);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return DimensionSpacePointSet
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findVisibleDimensionSpacePointsOfNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): DimensionSpacePointSet {
        $connection = $this->client->getConnection();

        $query = 'SELECT h.dimensionspacepoint, h.dimensionspacepointhash FROM neos_contentgraph_node n
                      INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                      WHERE n.nodeaggregateidentifier = :nodeAggregateIdentifier
                      AND h.contentstreamidentifier = :contentStreamIdentifier';
        $parameters = [
            'nodeAggregateIdentifier' => $nodeAggregateIdentifier,
            'contentStreamIdentifier' => $contentStreamIdentifier
        ];
        $dimensionSpacePoints = [];
        foreach ($connection->executeQuery($query, $parameters)->fetchAll() as $hierarchyRelationData) {
            $dimensionSpacePoints[$hierarchyRelationData['dimensionspacepointhash']] = new DimensionSpacePoint(json_decode($hierarchyRelationData['dimensionspacepoint'], true));
        }

        return new DimensionSpacePointSet($dimensionSpacePoints);
    }

    public function countNodes(): int
    {
        $connection = $this->client->getConnection();
        $query = 'SELECT COUNT(*) FROM neos_contentgraph_node';

        return (int) $connection->executeQuery($query)->fetch()['COUNT(*)'];
    }

    public function enableCache(): void
    {
        if (is_array($this->subgraphs)) {
            foreach ($this->subgraphs as $subgraph) {
                $subgraph->getInMemoryCache()->enable();
            }
        }
    }

    public function disableCache(): void
    {
        if (is_array($this->subgraphs)) {
            foreach ($this->subgraphs as $subgraph) {
                $subgraph->getInMemoryCache()->disable();
            }
        }
    }
}
