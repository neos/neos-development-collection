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
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphChildQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphParentQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\QueryUtility;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\CoverageByOrigin;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregates;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\OriginByCoverage;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
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
final readonly class PostgresContentGraph implements ContentGraphInterface
{

    private ContentGraphTableNames $tableNames;

    public function __construct(
        private Connection $dbal,
        private PropertyConverter $propertyConverter,
        private NodeFactory $nodeFactory,
        private ContentRepositoryId $contentRepositoryId,
        private NodeTypeManager $nodeTypeManager,
        public WorkspaceName $workspaceName,
        public ContentStreamId $contentStreamId
    ) {
        $this->tableNames = ContentGraphTableNames::create($this->contentRepositoryId);
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
        return new PostgresContentSubgraph(
            $this->contentRepositoryId,
            $this->contentStreamId,
            $this->workspaceName,
            $dimensionSpacePoint,
            $visibilityConstraints,
            $this->dbal,
            $this->propertyConverter,
            $this->nodeFactory,
            $this->nodeTypeManager,
            $this->tableNames
        );
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
            throw new \RuntimeException(
                sprintf(
                    'More than one root node aggregate of type "%s" found (IDs: %s).',
                    $nodeTypeName->value,
                    implode(', ', $ids)
                )
            );
        }

        return $rootNodeAggregates->first();
    }

    public function findRootNodeAggregates(
        FindRootNodeAggregatesFilter $filter,
    ): NodeAggregates {
        $result = $this->dbal->executeQuery(
            <<<SQL
                select
                    n.*,
                    h.contentstreamid,
                    dsp.dimensionspacepoint,
                    h.subtreetags->(n.relationanchorpoint::text) as subtreetags
                from {$this->tableNames->node()} n
                    inner join {$this->tableNames->hierarchyRelation()} h
                        on n.relationanchorpoint = any(h.childnodeanchors)
                    inner join {$this->tableNames->dimensionSpacePoints()} dsp
                        on dsp.hash = h.dimensionspacepointhash
                where h.contentstreamid = :contentstream_id
                  and h.parentnodeanchor = :root_edge_parent_anchor_id
                  -- optional filter
                  and (:nodetype_filter::varchar is null or n.nodetypename = :nodetype_filter)
            SQL,
            [
                'contentstream_id' => $this->contentStreamId->value,
                'nodetype_filter' => $filter->nodeTypeName?->value,
                'root_edge_parent_anchor_id' => NodeRelationAnchorPoint::forRootEdge()->value
            ]
        );

        return $this->mapResultsToNodeAggregates($result);
    }

    public function findNodeAggregatesByType(
        NodeTypeName $nodeTypeName
    ): NodeAggregates {
        $query = HypergraphQuery::create($this->contentStreamId, $this->tableNames, false);
        $query = $query->withNodeTypeName($nodeTypeName);

        $nodeRows = $query->execute($this->dbal)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            $this->workspaceName,
            VisibilityConstraints::createEmpty()
        );
    }

    public function findNodeAggregateById(
        NodeAggregateId $nodeAggregateId
    ): ?NodeAggregate {


        $query = HypergraphQuery::create($this->contentStreamId, $this->tableNames, false);
        $query = $query->withNodeAggregateId($nodeAggregateId);

        $nodeRows = $query->execute($this->dbal)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            $this->workspaceName,
            VisibilityConstraints::createEmpty()
        );
    }

    public function findNodeAggregatesByIds(
        NodeAggregateIds $nodeAggregateIds
    ): NodeAggregates {
        $query = HypergraphQuery::create($this->contentStreamId, $this->tableNames, false);
        $query = $query->withNodeAggregateIds($nodeAggregateIds);

        $nodeRows = $query->execute($this->dbal)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            $this->workspaceName,
            VisibilityConstraints::createEmpty()
        );
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate {
        $query = /** @lang PostgreSQL */
            '
            SELECT n.origindimensionspacepoint, n.nodeaggregateid, n.nodetypename, n.created, n.originalcreated, n.lastmodified, n.originallastmodified,
                   n.classification, n.properties, n.nodename, ph.contentstreamid, ph.dimensionspacepoint,
                   ph.subtreetags->(n.relationanchorpoint::text) as subtreetags
                FROM ' . $this->tableNames->hierarchyRelation() . ' ph
                JOIN ' . $this->tableNames->node() . ' n ON n.relationanchorpoint = ANY(ph.childnodeanchors)
            WHERE ph.contentstreamid = :contentStreamId
                AND n.nodeaggregateid = (
                    SELECT pn.nodeaggregateid
                        FROM ' . $this->tableNames->node() . ' pn
                        JOIN ' . $this->tableNames->hierarchyRelation() . ' ch
                            ON pn.relationanchorpoint = ch.parentnodeanchor
                        JOIN ' . $this->tableNames->node() . ' cn ON cn.relationanchorpoint = ANY(ch.childnodeanchors)
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
            $this->workspaceName,
            VisibilityConstraints::createEmpty()
        );
    }

    public function findParentNodeAggregates(
        NodeAggregateId $childNodeAggregateId
    ): NodeAggregates {
        $query = HypergraphParentQuery::create($this->contentStreamId, $this->tableNames);
        $query = $query->withChildNodeAggregateId($childNodeAggregateId);

        $nodeRows = $query->execute($this->dbal)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            $this->workspaceName,
            VisibilityConstraints::createEmpty()
        );
    }

    public function findAncestorNodeAggregateIds(NodeAggregateId $entryNodeAggregateId): NodeAggregateIds
    {
        $stack = iterator_to_array($this->findParentNodeAggregates($entryNodeAggregateId));

        $ancestorNodeAggregateIds = [];
        while ($stack !== []) {
            $nodeAggregate = array_shift($stack);
            $ancestorNodeAggregateIds[] = $nodeAggregate->nodeAggregateId;
            array_push($stack, ...iterator_to_array($this->findParentNodeAggregates($nodeAggregate->nodeAggregateId)));
        }
        return NodeAggregateIds::fromArray($ancestorNodeAggregateIds);
    }

    public function findChildNodeAggregates(
        NodeAggregateId $parentNodeAggregateId
    ): NodeAggregates {
        $query = HypergraphChildQuery::create(
            $this->contentStreamId,
            $parentNodeAggregateId,
            $this->tableNames
        );

        $nodeRows = $query->execute($this->dbal)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            $this->workspaceName,
            VisibilityConstraints::createEmpty()
        );
    }

    public function findChildNodeAggregateByName(
        NodeAggregateId $parentNodeAggregateId,
        NodeName $name
    ): ?NodeAggregate {
        $query = HypergraphChildQuery::create(
            $this->contentStreamId,
            $parentNodeAggregateId,
            $this->tableNames
        );
        $query = $query->withChildNodeName($name);

        $nodeRows = $query->execute($this->dbal)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregate(
            $nodeRows,
            $this->workspaceName,
            VisibilityConstraints::createEmpty()
        );
    }

    public function findTetheredChildNodeAggregates(
        NodeAggregateId $parentNodeAggregateId
    ): NodeAggregates {
        $query = HypergraphChildQuery::create(
            $this->contentStreamId,
            $parentNodeAggregateId,
            $this->tableNames
        );
        $query = $query->withOnlyTethered();

        $nodeRows = $query->execute($this->dbal)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            $this->workspaceName,
            VisibilityConstraints::createEmpty()
        );
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
            $this->tableNames,
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

    public function findNodeAggregatesTaggedBy(SubtreeTag $subtreeTag): NodeAggregates
    {
        // In the hypergraph model, subtree tags are stored as:
        // {"<childAnchor>": {"<tagName>": true/null}, ...}
        // We need to find nodes where their anchor has the tag set to true (explicitly tagged)
        $query = /** @lang PostgreSQL */
            'SELECT n.origindimensionspacepoint, n.nodeaggregateid,
                n.nodetypename, n.classification, n.properties, n.nodename,
                h.contentstreamid, h.dimensionspacepoint,
                h.subtreetags->(n.relationanchorpoint::text) as subtreetags
            FROM ' . $this->tableNames->hierarchyRelation() . ' th
            JOIN ' . $this->tableNames->node() . ' tn ON tn.relationanchorpoint = ANY(th.childnodeanchors)
            JOIN ' . $this->tableNames->hierarchyRelation() . ' h
                ON h.contentstreamid = th.contentstreamid
            JOIN ' . $this->tableNames->node() . ' n ON n.relationanchorpoint = ANY(h.childnodeanchors)
                AND n.nodeaggregateid = tn.nodeaggregateid
            WHERE th.contentstreamid = :contentStreamId
              AND h.contentstreamid = :contentStreamId
              AND (th.subtreetags->(tn.relationanchorpoint::text)->>:tagName) = \'true\'
            ORDER BY n.relationanchorpoint DESC';

        $nodeRows = $this->dbal->executeQuery($query, [
            'contentStreamId' => $this->contentStreamId->value,
            'tagName' => $subtreeTag->value,
        ])->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $nodeRows,
            $this->workspaceName,
            VisibilityConstraints::createEmpty()
        );
    }

    public function findUsedNodeTypeNames(): NodeTypeNames
    {
        $rows = $this->dbal->executeQuery(
            'SELECT DISTINCT nodetypename FROM ' . $this->tableNames->node()
        )->fetchAllAssociative();

        return NodeTypeNames::fromArray(array_map(
            static fn (array $row) => NodeTypeName::fromString($row['nodetypename']),
            $rows
        ));
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return NodeAggregates
     */
    private function mapResultsToNodeAggregates(Result $result): NodeAggregates
    {
        return $this->nodeFactory->mapNodeRowsToNodeAggregates(
            $result->fetchAllAssociative(),
            $this->workspaceName,
            VisibilityConstraints::createEmpty()
        );
    }
}
