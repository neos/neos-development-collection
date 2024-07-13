<?php

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphChildQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphParentQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphQuery;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregates;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The PostgreSQL adapter content hypergraph
 *
 * To be used as a read-only source of subhypergraphs, node aggregates and nodes
 *
 * @internal but the parent {@see ContentGraphInterface} is API
 */
final class ContentHypergraph implements ContentGraphInterface
{
    /**
     * @var array|ContentSubhypergraph[]
     */
    private array $subhypergraphs;

    public function __construct(
        private readonly Connection $dbal,
        private readonly NodeFactory $nodeFactory,
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly string $tableNamePrefix,
        public readonly WorkspaceName $workspaceName,
        public readonly ContentStreamId $contentStreamId
    ) {
    }

    public function getContentRepositoryId(): ContentRepositoryId
    {
        return $this->contentRepositoryId;
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public function getSubgraph(
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface {
        $index = $this->contentStreamId->value . '-' . $dimensionSpacePoint->hash . '-' . $visibilityConstraints->getHash();
        if (!isset($this->subhypergraphs[$index])) {
            $this->subhypergraphs[$index] = new ContentSubhypergraph(
                $this->contentRepositoryId,
                $this->contentStreamId,
                $this->workspaceName,
                $dimensionSpacePoint,
                $visibilityConstraints,
                $this->dbal,
                $this->nodeFactory,
                $this->nodeTypeManager,
                $this->tableNamePrefix
            );
        }

        return $this->subhypergraphs[$index];
    }

    public function findRootNodeAggregateByType(
        NodeTypeName $nodeTypeName
    ): ?NodeAggregate {
        $rootNodeAggregates = $this->findRootNodeAggregates(
            FindRootNodeAggregatesFilter::create(nodeTypeName: $nodeTypeName)
        );

        if ($rootNodeAggregates->count() > 1) {
            $ids = [];
            foreach ($rootNodeAggregates as $rootNodeAggregate) {
                $ids[] = $rootNodeAggregate->nodeAggregateId->value;
            }
            throw new \RuntimeException(sprintf(
                'More than one root node aggregate of type "%s" found (IDs: %s).',
                $nodeTypeName->value,
                implode(', ', $ids)
            ));
        }

        return $rootNodeAggregates->first();
    }

    public function findRootNodeAggregates(
        FindRootNodeAggregatesFilter $filter,
    ): NodeAggregates {
        throw new \BadMethodCallException('method findRootNodeAggregates is not implemented yet.', 1645782874);
    }

    public function findNodeAggregatesByType(
        NodeTypeName $nodeTypeName
    ): NodeAggregates {
        return NodeAggregates::createEmpty();
    }

    public function findNodeAggregateById(
        NodeAggregateId $nodeAggregateId
    ): ?NodeAggregate {
        $query = HypergraphQuery::create($this->contentStreamId, $this->tableNamePrefix, true);
        $query = $query->withNodeAggregateId($nodeAggregateId);

        $nodeRows = $query->execute($this->dbal)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate {
        $query = /** @lang PostgreSQL */ '
            SELECT n.origindimensionspacepoint, n.nodeaggregateid, n.nodetypename,
                   n.classification, n.properties, n.nodename, ph.contentstreamid, ph.dimensionspacepoint
                FROM ' . $this->tableNamePrefix . '_hierarchyhyperrelation ph
                JOIN ' . $this->tableNamePrefix . '_node n ON n.relationanchorpoint = ANY(ph.childnodeanchors)
            WHERE ph.contentstreamid = :contentStreamId
                AND n.nodeaggregateid = (
                    SELECT pn.nodeaggregateid
                        FROM ' . $this->tableNamePrefix . '_node pn
                        JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation ch
                            ON pn.relationanchorpoint = ch.parentnodeanchor
                        JOIN ' . $this->tableNamePrefix . '_node cn ON cn.relationanchorpoint = ANY(ch.childnodeanchors)
                    WHERE cn.nodeaggregateid = :childNodeAggregateId
                        AND cn.origindimensionspacepointhash = :childOriginDimensionSpacePointHash
                        AND ch.dimensionspacepointhash = :childOriginDimensionSpacePointHash
                        AND ch.contentstreamid = :contentStreamId
                )';
        $parameters = [
            'contentStreamId' => $this->contentStreamId->value,
            'childNodeAggregateId' => $childNodeAggregateId->value,
            'childOriginDimensionSpacePointHash' => $childOriginDimensionSpacePoint->hash
        ];

        $nodeRows = $this->dbal->executeQuery(
            $query,
            $parameters
        )->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    public function findParentNodeAggregates(
        NodeAggregateId $childNodeAggregateId
    ): NodeAggregates {
        $query = HypergraphParentQuery::create($this->contentStreamId, $this->tableNamePrefix);
        $query = $query->withChildNodeAggregateId($childNodeAggregateId);

        $nodeRows = $query->execute($this->dbal)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    public function findChildNodeAggregates(
        NodeAggregateId $parentNodeAggregateId
    ): NodeAggregates {
        $query = HypergraphChildQuery::create(
            $this->contentStreamId,
            $parentNodeAggregateId,
            $this->tableNamePrefix
        );

        $nodeRows = $query->execute($this->dbal)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    public function findChildNodeAggregateByName(
        NodeAggregateId $parentNodeAggregateId,
        NodeName $name
    ): ?NodeAggregate {
        $query = HypergraphChildQuery::create(
            $this->contentStreamId,
            $parentNodeAggregateId,
            $this->tableNamePrefix
        );
        $query = $query->withChildNodeName($name);

        $nodeRows = $query->execute($this->dbal)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            VisibilityConstraints::withoutRestrictions()
        );
    }

    public function findTetheredChildNodeAggregates(
        NodeAggregateId $parentNodeAggregateId
    ): NodeAggregates {
        $query = HypergraphChildQuery::create(
            $this->contentStreamId,
            $parentNodeAggregateId,
            $this->tableNamePrefix
        );
        $query = $query->withOnlyTethered();

        $nodeRows = $query->execute($this->dbal)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates($nodeRows, VisibilityConstraints::withoutRestrictions());
    }

    public function getDimensionSpacePointsOccupiedByChildNodeName(
        NodeName $nodeName,
        NodeAggregateId $parentNodeAggregateId,
        OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePointsToCheck
    ): DimensionSpacePointSet {
        $query = HypergraphChildQuery::create(
            $this->contentStreamId,
            $parentNodeAggregateId,
            $this->tableNamePrefix,
            ['ch.dimensionspacepoint, ch.dimensionspacepointhash']
        );
        $query = $query->withChildNodeName($nodeName)
            ->withOriginDimensionSpacePoint($parentNodeOriginDimensionSpacePoint)
            ->withDimensionSpacePoints($dimensionSpacePointsToCheck);

        $occupiedDimensionSpacePoints = [];
        foreach ($query->execute($this->dbal)->fetchAllAssociative() as $row) {
            $occupiedDimensionSpacePoints[$row['dimensionspacepointhash']]
                = DimensionSpacePoint::fromJsonString($row['dimensionspacepoint']);
        }

        return new DimensionSpacePointSet($occupiedDimensionSpacePoints);
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function countNodes(): int
    {
        $query = 'SELECT COUNT(*) FROM ' . $this->tableNamePrefix . '_node';

        return $this->dbal->executeQuery($query)->fetchOne();
    }

    public function findUsedNodeTypeNames(): NodeTypeNames
    {
        return NodeTypeNames::createEmpty();
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }
}
