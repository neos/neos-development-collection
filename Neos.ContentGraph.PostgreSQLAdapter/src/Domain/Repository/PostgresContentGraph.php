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
                    dsp.dimensionspacepoint
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
        return NodeAggregates::createEmpty();
    }

    private function findNodeAgg_old(
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

    public function findNodeAggregateById(
        NodeAggregateId $nodeAggregateId
    ): ?NodeAggregate {

        //return $this->findNodeAgg_old($nodeAggregateId);

        $parameters = [
            'nodeaggregateid' => $nodeAggregateId->value,
            'contentstreamid' => $this->contentStreamId->value
        ];

        $query = <<<SQL
            with aggregate_nodes as (
                  -- find all node variants for this aggregate ID
                  select *
                  from cr_default_p_graph_node an
                  where an.nodeaggregateid = :nodeaggregateid)
            select an.nodetypename,
                   an.nodename,
                   an.classification,
                   jsonb_object_agg(an.origindimensionspacepointhash, an.origindimensionspacepoint)
                                                                         as occupied_dsps,
                   jsonb_agg(jsonb_build_object(
                     'dimensionspacepointhash', h.dimensionspacepointhash,
                     'dimensionspacepoint', h.dimensionspacepoint,
                     'origindimensionspacepoint', an.origindimensionspacepoint,
                     'properties', an.properties
                                                                      )) as nodes_by_covered_dsp,
                   jsonb_object_agg(h.dimensionspacepointhash, subtree_tags.tags)
                                                                         as subtreetags_by_covered
            -- aggregations
            from aggregate_nodes an
                   -- hierarchy relation for variants
                   left join cr_default_p_graph_hierarchyrelation h
                             on an.relationanchorpoint = any (h.childnodeanchors)
                               and h.contentstreamid = :contentstreamid
              -- subtree tags for each variant
              -- TODO mehr subtree logik / vererbung? let's see...
                   left join lateral (
               -- TODO expose function?
              with all_affected_subtrees as (select *
                                             from cr_default_p_graph_subtreetags st
                                             where :nodeaggregateid = any (st.affectednodeaggregateids)
                                               and st.contentstreamid = :contentstreamid
                                               and st.dimensionspacepointhash = h.dimensionspacepointhash)
              select
                -- Since there is no removal of tags down the inheritance chain,
                -- we can simply add together all parent tags without having to look at the
                -- inheritance chain order.
                jsonb_build_object(
                  'explicit_tags', (select jsonb_agg(t.tag)
                                     from (select distinct unnest(expl_st.subtreetags)
                                           from all_affected_subtrees expl_st
                                           -- include only explicitly set tags
                                           where expl_st.originnodeaggregateid = :nodeaggregateid) t(tag)
                       ),
                  'only_inherited', (select jsonb_agg(t.tag)
                                     from (select distinct unnest(expl_st.subtreetags)
                                           from all_affected_subtrees expl_st
                                           -- exclude explicitly set tags
                                           where expl_st.originnodeaggregateid != :nodeaggregateid) t(tag))
                ) as tags
              ) subtree_tags on true
            group by an.nodetypename, an.nodename, an.classification
        SQL;

        $result = $this->dbal->executeQuery($query, $parameters);
        $aggregateRow = $result->fetchAssociative();
        if (!is_array($aggregateRow)) {
            return null;
        }

        $classification = NodeAggregateClassification::from($aggregateRow['classification']);
        $nodeTypeName = NodeTypeName::fromString($aggregateRow['nodetypename']);
        $nodeNameValue = $aggregateRow['nodename'];
        $nodeName = !empty($nodeNameValue) ? NodeName::fromString($nodeNameValue) : null;

        $subtreeTagsByCovered = json_decode($aggregateRow['subtreetags_by_covered'], true);
        $subtreeTagsByCoveredDeserialized = array_map(function ($subtreeTags) {
            return NodeTags::create(
                SubtreeTags::fromStrings(...($subtreeTags['explicit_tags'] ?? [])),
                SubtreeTags::fromStrings(...($subtreeTags['only_inherited'] ?? [])),
            );
        }, $subtreeTagsByCovered);

        $nodesByCovered = json_decode($aggregateRow['nodes_by_covered_dsp'], true);
        $nodesByOccupiedDeserialized = [];
        $coveredDimensionSpacePoints = [];
        $coverageByOccupant = [];
        $occupationByCovered = [];
        foreach ($nodesByCovered as $nodeJson) {
            $coveredDSPHash = $nodeJson['dimensionspacepointhash'];
            $coveredDSP = DimensionSpacePoint::fromArray($nodeJson['dimensionspacepoint']);
            $occupiedDSP = OriginDimensionSpacePoint::fromArray($nodeJson['origindimensionspacepoint']);
            $coveredDimensionSpacePoints[$coveredDSPHash] = $coveredDSP;
            $coverageByOccupant[$occupiedDSP->hash][$coveredDSPHash] = $coveredDSP;
            $occupationByCovered[$coveredDSPHash] = $occupiedDSP;
            $nodesByOccupiedDeserialized[$occupiedDSP->hash] = Node::create(
                $this->contentRepositoryId,
                $this->workspaceName,
                $coveredDSP,
                $nodeAggregateId,
                $occupiedDSP,
                $classification,
                $nodeTypeName,
                new PropertyCollection(
                    SerializedPropertyValues::fromArray($nodeJson['properties']),
                    $this->propertyConverter
                ),
                $nodeName,
                $subtreeTagsByCoveredDeserialized[$coveredDSP->hash],
                Timestamps::create(
                // TODO replace with $nodeRow['created'] and $nodeRow['originalcreated'] once projection has implemented support
                    QueryUtility::parseDateTimeString('2023-03-17 12:00:00'),
                    QueryUtility::parseDateTimeString('2023-03-17 12:00:00'),
                    null,
                    null,
                ),
                // when looking at a node aggregate, there are no visibility constraints
                VisibilityConstraints::createEmpty()
            );
        }

        return NodeAggregate::create(
            $this->contentRepositoryId,
            $this->workspaceName,
            $nodeAggregateId,
            $classification,
            $nodeTypeName,
            $nodeName,
            OriginDimensionSpacePointSet::fromArray(json_decode($aggregateRow['occupied_dsps'], true)),
            // FIXME: !!! we currently assume, that there is no explicit test coverage for the node contained in a queried NodeAggregate, so we might need to add them.
            // TODO: discuss, to extend the Neos core with the following distinction:
            //    - Problem: NodeAggregate often dont need access to its containing node instances, especially not the node properties.
            //               In this layer of abstraction we cannot distinguish those two cases, so we ALWAYS load and instantiate all nodes in the aggregate
            //               even if we might not need them in all cases. -> idea: add another object (like a NodeAggregate"Light" and another API function)
            //               OR: do we need the properties at all.
            $nodesByOccupiedDeserialized,
            CoverageByOrigin::fromArray($coverageByOccupant),
            new DimensionSpacePointSet($coveredDimensionSpacePoints),
            OriginByCoverage::fromArray($occupationByCovered),
            $subtreeTagsByCoveredDeserialized
        );
    }

    public function findNodeAggregatesByIds(
        NodeAggregateIds $nodeAggregateIds
    ): NodeAggregates {
        throw new \BadMethodCallException(sprintf('Not implemented'), 1740572440);
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ?NodeAggregate {
        $query = /** @lang PostgreSQL */
            '
            SELECT n.origindimensionspacepoint, n.nodeaggregateid, n.nodetypename,
                   n.classification, n.properties, n.nodename, ph.contentstreamid, ph.dimensionspacepoint
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
        throw new \BadMethodCallException('Not implemented.', 1740574672);
    }

    public function findUsedNodeTypeNames(): NodeTypeNames
    {
        return NodeTypeNames::createEmpty();
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
