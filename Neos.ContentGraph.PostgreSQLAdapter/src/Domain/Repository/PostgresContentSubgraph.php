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
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphChildQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphParentQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphReferenceQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphSiblingQuery;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\HypergraphSiblingQueryMode;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\Query\QueryUtility;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\ExpandedNodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtrees;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The content subgraph application repository
 *
 * To be used as a read-only source of nodes.
 *
 * ## Conventions for SQL queries
 *
 * - n -> node
 * - h -> hierarchy hyperrelation
 *
 * - if more than one node (parent-child)
 *   - pn -> parent node
 *   - cn -> child node
 *   - h -> the hierarchy hyperrelation connecting parent and children
 *   - ph -> the hierarchy hyperrelation incoming to the parent (sometimes relevant)
 *
 * @internal but the public {@see ContentSubgraphInterface} is API
 */
final readonly class PostgresContentSubgraph implements ContentSubgraphInterface
{

    private const DEFAULT_SIBLING_NODE_LIMIT = 100000;

    private array $excludedSubtreeTags;
    private bool $excludedSubtreeTagsFilterActive;

    public function __construct(
        private ContentRepositoryId $contentRepositoryId,
        private ContentStreamId $contentStreamId,
        private WorkspaceName $workspaceName,
        private DimensionSpacePoint $dimensionSpacePoint,
        private VisibilityConstraints $visibilityConstraints,
        private Connection $dbal,
        private PropertyConverter $propertyConverter,
        private NodeFactory $nodeFactory,
        private NodeTypeManager $nodeTypeManager,
        private ContentGraphTableNames $tableNames
    ) {
        $excludedSubtreeTags = $this->visibilityConstraints->excludedSubtreeTags->toStringArray();
        if (count($excludedSubtreeTags) === 0) {
            $this->excludedSubtreeTags = [];
            $this->excludedSubtreeTagsFilterActive = false;
        } else {
            $this->excludedSubtreeTags = $excludedSubtreeTags;
            $this->excludedSubtreeTagsFilterActive = true;
        }
    }

    public function getContentRepositoryId(): ContentRepositoryId
    {
        return $this->contentRepositoryId;
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    public function getVisibilityConstraints(): VisibilityConstraints
    {
        return $this->visibilityConstraints;
    }

    public function findNodeById(NodeAggregateId $nodeAggregateId): ?Node
    {
        $query = HypergraphQuery::create($this->contentStreamId, $this->tableNames);
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withNodeAggregateId($nodeAggregateId)
            ->withRestriction($this->visibilityConstraints);

        $nodeRow = $query->execute($this->dbal)->fetchAssociative();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $this->workspaceName,
            $this->visibilityConstraints,
            $this->dimensionSpacePoint
        ) : null;
    }
    /*
    public function findNodeById(NodeAggregateId $nodeAggregateId): ?Node
    {
        $parameters = [
            // from ContentSubgraph
            'contentstreamid' => $this->contentStreamId->value,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->hash,
            'subtreetag_filter_active' => $this->excludedSubtreeTagsFilterActive,
            'excluded_subtreetags' => $this->excludedSubtreeTags,
            // from parameter input
            'nodeaggregateid' => $nodeAggregateId->value
        ];

        $parameterTypes = [
            'excluded_subtreetags' => Connection::PARAM_STR_ARRAY,
        ];

        $query = <<<SQL
            select
                n.origindimensionspacepoint,
                n.classification,
                n.nodetypename,
                n.properties,
                n.nodename,
                subtree_tags.tags
            from {$this->tableNames->node()} n
                left join {$this->tableNames->hierarchyRelation()} h
                    on n.relationanchorpoint = any(h.childnodeanchors)
                   and h.contentstreamid = :contentstreamid
                   and h.dimensionspacepointhash = :dimensionspacepointhash
                left join lateral (
                    with all_affected_subtrees as (
                        select *
                        from {$this->tableNames->subTreeRelation()} st
                        where n.nodeaggregateid = any (st.affected_nodeaggregateids)
                          and st.contentstreamid = :contentstreamid
                          and st.dimensionspacepointhash = :dimensionspacepointhash
                    )
                    select
                      -- Since there is no removal of tags down the inheritance chain,
                      -- we can simply add together all parent tags without having to look at the
                      -- inheritance chain order.
                      jsonb_build_object(
                        'explicit_tags', (select jsonb_agg(t.tag)
                                           from (select distinct unnest(expl_st.subtreetags)
                                                 from all_affected_subtrees expl_st
                                                 -- include only explicitly set tags
                                                 where expl_st.nodeaggregateid = n.nodeaggregateid) t(tag)
                             ),
                        'only_inherited', (select jsonb_agg(t.tag)
                                           from (select distinct unnest(expl_st.subtreetags)
                                                 from all_affected_subtrees expl_st
                                                 -- exclude explicitly set tags
                                                 where expl_st.nodeaggregateid != n.nodeaggregateid) t(tag))
                      ) as tags
                ) subtree_tags on true
            where n.nodeaggregateid = :nodeaggregateid
                -- subtree tag filter
              and (
                  -- deactivate filter when no values are set
                  not :subtreetag_filter_active
                    or
                  not exists(
                    select 1
                    from {$this->tableNames->subTreeRelation()} st
                    where :nodeaggregateid = any(st.affected_nodeaggregateids)
                      and st.dimensionspacepointhash = :dimensionspacepointhash
                      and st.contentstreamid = :contentstreamid
                      and st.subtreetags && array[:excluded_subtreetags]::varchar(36)[]
                  )
              )
        SQL;

        $result = $this->dbal->executeQuery($query, $parameters, $parameterTypes);
        $nodeRow = $result->fetchAssociative();
        if ($nodeRow === false) {
            return null;
        }

        $subtreeTags = json_decode($nodeRow['tags'], true);
        return Node::create(
            $this->contentRepositoryId,
            $this->workspaceName,
            $this->dimensionSpacePoint,
            $nodeAggregateId,
            OriginDimensionSpacePoint::fromJsonString($nodeRow['origindimensionspacepoint']),
            NodeAggregateClassification::from($nodeRow['classification']),
            NodeTypeName::fromString($nodeRow['nodetypename']),
            new PropertyCollection(
                SerializedPropertyValues::fromJsonString($nodeRow['properties']),
                $this->propertyConverter
            ),
            !empty($nodeRow['nodename']) ? NodeName::fromString($nodeRow['nodename']) : null,
            NodeTags::create(
                SubtreeTags::fromStrings(...($subtreeTags['explicit_tags'] ?? [])),
                SubtreeTags::fromStrings(...($subtreeTags['only_inherited'] ?? [])),
            ),
            Timestamps::create(
            // TODO replace with $nodeRow['created'] and $nodeRow['originalcreated'] once projection has implemented support
                QueryUtility::parseDateTimeString('2023-03-17 12:00:00'),
                QueryUtility::parseDateTimeString('2023-03-17 12:00:00'),
                null,
                null,
            ),
            $this->visibilityConstraints,
        );
    }
    */

    public function findNodesByIds(NodeAggregateIds $nodeAggregateIds): Nodes
    {
        throw new \BadMethodCallException(sprintf('Not implemented'), 1740572440);
    }

    public function findRootNodeByType(NodeTypeName $nodeTypeName): ?Node
    {
        $query = HypergraphQuery::create($this->contentStreamId, $this->tableNames);
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withNodeTypeName($nodeTypeName)
            ->withClassification(NodeAggregateClassification::CLASSIFICATION_ROOT)
            ->withRestriction($this->visibilityConstraints);

        $nodeRow = $query->execute($this->dbal)->fetchAssociative();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $this->workspaceName,
            $this->visibilityConstraints,
            $this->dimensionSpacePoint
        ) : null;
    }

    public function findChildNodes(
        NodeAggregateId $parentNodeAggregateId,
        FindChildNodesFilter $filter
    ): Nodes {
        $query = $this->buildChildNodesQuery($parentNodeAggregateId, $filter);
        $query = $query->withPositionOrdering();
        if ($filter->pagination !== null) {
            $query = $query
                ->withLimit($filter->pagination->limit)
                ->withOffset($filter->pagination->offset);
        }

        $childNodeRows = $query->execute($this->dbal)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodes(
            $childNodeRows,
            $this->workspaceName,
            $this->visibilityConstraints
        );
    }

    public function countChildNodes(NodeAggregateId $parentNodeAggregateId, Filter\CountChildNodesFilter $filter): int
    {
        $query = $this->buildChildNodesQuery($parentNodeAggregateId, $filter);

        $countSql = 'SELECT COUNT(*) FROM (' . $query->getQuery() . ') AS countquery';
        $result = $this->dbal->executeQuery($countSql, $query->getParameters(), $query->getTypes());

        return (int)$result->fetchOne();
    }

    private function buildChildNodesQuery(
        NodeAggregateId $parentNodeAggregateId,
        FindChildNodesFilter|Filter\CountChildNodesFilter $filter
    ): HypergraphChildQuery {
        $query = HypergraphChildQuery::create(
            $this->contentStreamId,
            $parentNodeAggregateId,
            $this->tableNames,
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withRestriction($this->visibilityConstraints);

        if ($filter->nodeTypes !== null) {
            $expandedNodeTypeCriteria = ExpandedNodeTypeCriteria::create(
                $filter->nodeTypes,
                $this->nodeTypeManager
            );
            $query = $query->withNodeTypeCriteria($expandedNodeTypeCriteria, 'cn');
        }

        return $query;
    }

    public function findReferences(
        NodeAggregateId $nodeAggregateId,
        FindReferencesFilter $filter
    ): References {
        $query = HypergraphReferenceQuery::create(
            $this->contentStreamId,
            'tarn.*, tarh.contentstreamid, tarh.dimensionspacepoint, tarh.subtreetags->(tarn.relationanchorpoint::text) as subtreetags',
            $this->tableNames
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withSourceNodeAggregateId($nodeAggregateId)
            ->withSourceRestriction($this->visibilityConstraints)
            ->withTargetRestriction($this->visibilityConstraints);

        if ($filter->nodeTypes) {
            $expandedNodeTypeCriteria = ExpandedNodeTypeCriteria::create(
                $filter->nodeTypes,
                $this->nodeTypeManager
            );
            $query = $query->withNodeTypeCriteria($expandedNodeTypeCriteria, 'tarn');
        }
        $orderings = [];
        if ($filter->referenceName) {
            $query = $query->withReferenceName($filter->referenceName);
        } else {
            $orderings[] = 'r.name';
        }
        $orderings[] = 'r.position';
        $orderings[] = 'tarn.nodeaggregateid';
        $query = $query->orderedBy($orderings);
        if (!is_null($filter->pagination)) {
            $query = $query
                ->withLimit($filter->pagination->limit)
                ->withOffset($filter->pagination->offset);
        }

        $referenceRows = $query->execute($this->dbal)->fetchAllAssociative();

        return $this->nodeFactory->mapReferenceRowsToReferences(
            $referenceRows,
            $this->workspaceName,
            $this->visibilityConstraints
        );
    }

    public function countReferences(NodeAggregateId $nodeAggregateId, Filter\CountReferencesFilter $filter): int
    {
        // TODO: Implement countReferences() method.
        return 0;
    }

    public function findBackReferences(
        NodeAggregateId $nodeAggregateId,
        FindBackReferencesFilter $filter
    ): References {
        $query = HypergraphReferenceQuery::create(
            $this->contentStreamId,
            'srcn.*, srch.contentstreamid, srch.dimensionspacepoint, srch.subtreetags->(srcn.relationanchorpoint::text) as subtreetags',
            $this->tableNames
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withTargetNodeAggregateId($nodeAggregateId)
            ->withSourceRestriction($this->visibilityConstraints)
            ->withTargetRestriction($this->visibilityConstraints);

        if ($filter->nodeTypes) {
            $expandedNodeTypeCriteria = ExpandedNodeTypeCriteria::create(
                $filter->nodeTypes,
                $this->nodeTypeManager
            );
            $query = $query->withNodeTypeCriteria($expandedNodeTypeCriteria, 'srcn');
        }
        $orderings = [];
        if ($filter->referenceName) {
            $query = $query->withReferenceName($filter->referenceName);
        } else {
            $orderings[] = 'r.name';
        }
        $orderings[] = 'r.position';
        $orderings[] = 'srcn.nodeaggregateid';
        $query = $query->orderedBy($orderings);
        if (!is_null($filter->pagination)) {
            $query = $query
                ->withLimit($filter->pagination->limit)
                ->withOffset($filter->pagination->offset);
        }

        $referenceRows = $query->execute($this->dbal)->fetchAllAssociative();

        return $this->nodeFactory->mapReferenceRowsToReferences(
            $referenceRows,
            $this->workspaceName,
            $this->visibilityConstraints
        );
    }

    public function countBackReferences(NodeAggregateId $nodeAggregateId, Filter\CountBackReferencesFilter $filter): int
    {
        // TODO: Implement countBackReferences() method.
        return 0;
    }

    public function findParentNode(NodeAggregateId $childNodeAggregateId): ?Node
    {
        $query = HypergraphParentQuery::create($this->contentStreamId, $this->tableNames);
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withRestriction($this->visibilityConstraints)
            ->withChildNodeAggregateId($childNodeAggregateId);

        $nodeRow = $query->execute($this->dbal)->fetchAssociative();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $this->workspaceName,
            $this->visibilityConstraints,
            $this->dimensionSpacePoint
        ) : null;
    }
    /*
    public function findNodeByPath(NodePath|NodeName $path, NodeAggregateId $startingNodeAggregateId): ?Node
    {
        $path = $path instanceof NodeName ? NodePath::fromNodeNames($path) : $path;

        // TODO validate if this is correct behavior
        if ($path->isEmpty()) {
            return $this->findNodeById($startingNodeAggregateId);
        }

        $parts = $path->getParts();
        $lastPathSegment = array_pop($parts);
        $parentPath = NodePath::fromNodeNames(...$parts);

        // this contains a trailing slash (unless it's empty)
        $relativeParentPathWithLeadingSlash = $parentPath->serializeToString();
        if ($relativeParentPathWithLeadingSlash !== '') {
            $relativeParentPathWithLeadingSlash = '/' . $relativeParentPathWithLeadingSlash;
        }


        $parameters = [
            // filters from ContentSubgraph
            'contentstreamid' => $this->contentStreamId->value,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->hash,
            'subtreetag_filter_active' => $this->excludedSubtreeTagsFilterActive,
            'excluded_subtreetags' => $this->excludedSubtreeTags,

            // from query input
            'starting_nodeaggregateid' => $startingNodeAggregateId->value,
            'relative_parent_path' => $relativeParentPathWithLeadingSlash,
            'last_path_segment' => $lastPathSegment->value
        ];
        $parameterTypes = [
            'excluded_subtreetags' => Connection::PARAM_STR_ARRAY
        ];

        $query = <<<SQL
            with starting_path as (
                select
                  case when ph.parentnodeanchor = 0 then '' else ph.parent_nodepath_absolute end as absolute_path
                from cr_default_p_graph_node pn
                       left join cr_default_p_graph_hierarchyrelation ph
                                 on pn.relationanchorpoint = any (ph.childnodeanchors)
                where ph.contentstreamid = :contentstreamid
                  and ph.dimensionspacepointhash = :dimensionspacepointhash
                  and pn.nodeaggregateid = :starting_nodeaggregateid
            )
            select
                n.nodeaggregateid,
                n.origindimensionspacepoint,
                n.classification,
                n.nodetypename,
                n.properties,
                n.nodename,
                subtree_tags.tags
            from starting_path spath, {$this->tableNames->hierarchyRelation()} h
                left join {$this->tableNames->node()} n
                    on n.relationanchorpoint = any(h.childnodeanchors) -- TODO hier falsch
                left join lateral (
                    with all_affected_subtrees as (
                        select *
                        from {$this->tableNames->subTreeRelation()} st
                        where n.nodeaggregateid = any (st.affected_nodeaggregateids)
                          and st.contentstreamid = :contentstreamid
                          and st.dimensionspacepointhash = :dimensionspacepointhash
                    )
                    select
                      -- Since there is no removal of tags down the inheritance chain,
                      -- we can simply add together all parent tags without having to look at the
                      -- inheritance chain order.
                      jsonb_build_object(
                        'explicit_tags', (select jsonb_agg(t.tag)
                                           from (select distinct unnest(expl_st.subtreetags)
                                                 from all_affected_subtrees expl_st
                                                 -- include only explicitly set tags
                                                 where expl_st.nodeaggregateid = n.nodeaggregateid) t(tag)
                             ),
                        'only_inherited', (select jsonb_agg(t.tag)
                                           from (select distinct unnest(expl_st.subtreetags)
                                                 from all_affected_subtrees expl_st
                                                 -- exclude explicitly set tags
                                                 where expl_st.nodeaggregateid != n.nodeaggregateid) t(tag))
                      ) as tags
                ) subtree_tags on true
            where h.parent_nodepath_absolute = spath.absolute_path || :relative_parent_path
              and n.nodename = :last_path_segment
              and h.contentstreamid = :contentstreamid
              and h.dimensionspacepointhash = :dimensionspacepointhash
                -- subtree tag filter
              and (
                  -- deactivate filter when no values are set
                  not :subtreetag_filter_active
                    or
                  not exists(
                    select 1
                    from {$this->tableNames->subTreeRelation()} st
                    where n.nodeaggregateid = any(st.affected_nodeaggregateids)
                      and st.dimensionspacepointhash = :dimensionspacepointhash
                      and st.contentstreamid = :contentstreamid
                      and st.subtreetags && array[:excluded_subtreetags]::varchar(36)[]
                  )
              )
        SQL;

        $result = $this->dbal->executeQuery($query, $parameters, $parameterTypes);
        $nodeRow = $result->fetchAssociative();

        if ($nodeRow === false) {
            return null;
        }

        $subtreeTags = json_decode($nodeRow['tags'], true);
        return Node::create(
            $this->contentRepositoryId,
            $this->workspaceName,
            $this->dimensionSpacePoint,
            NodeAggregateId::fromString($nodeRow['nodeaggregateid']),
            OriginDimensionSpacePoint::fromJsonString($nodeRow['origindimensionspacepoint']),
            NodeAggregateClassification::from($nodeRow['classification']),
            NodeTypeName::fromString($nodeRow['nodetypename']),
            new PropertyCollection(
                SerializedPropertyValues::fromJsonString($nodeRow['properties']),
                $this->propertyConverter
            ),
            !empty($nodeRow['nodename']) ? NodeName::fromString($nodeRow['nodename']) : null,
            NodeTags::create(
                SubtreeTags::fromStrings(...($subtreeTags['explicit_tags'] ?? [])),
                SubtreeTags::fromStrings(...($subtreeTags['only_inherited'] ?? [])),
            ),
            Timestamps::create(
            // TODO replace with $nodeRow['created'] and $nodeRow['originalcreated'] once projection has implemented support
                QueryUtility::parseDateTimeString('2023-03-17 12:00:00'),
                QueryUtility::parseDateTimeString('2023-03-17 12:00:00'),
                null,
                null,
            ),
            $this->visibilityConstraints,
        );
    }*/
    /*
    public function findNodeByAbsolutePath(AbsoluteNodePath $path): ?Node
    {
        // TODO validate if this is correct
        if ($path->isRoot()) {
            return $this->findRootNodeByType($path->rootNodeTypeName);
        }

        $parts = $path->getParts();
        $lastPathSegment = array_pop($parts);
        $parentPath = NodePath::fromNodeNames(...$parts);

        $parameters = [
            // filters from ContentSubgraph
            'contentstreamid' => $this->contentStreamId->value,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->hash,
            'subtreetag_filter_active' => $this->excludedSubtreeTagsFilterActive,
            'excluded_subtreetags' => $this->excludedSubtreeTags,

            // from query input
            'root_nodetype' => $path->rootNodeTypeName->value,
            'absolute_parent_path' => $parentPath->serializeToString(), // this contains NO trailing slash
            'last_path_segment' => $lastPathSegment->value
        ];
        $parameterTypes = [
            'excluded_subtreetags' => Connection::PARAM_STR_ARRAY
        ];

        $query = <<<SQL
            with subgraph_root_path as (
                select
                    re.nodename
                from {$this->tableNames->node()} rn
                    left join {$this->tableNames->hierarchyRelation()} re
                        on rn.relationanchorpoint = any(re.childnodeanchors)
                where rn.nodetypename = :root_nodetype
                  -- get the root edge
                  and re.parentnodeanchor = 0
                  and re.contentstreamid = :contentstreamid
                  and re.dimensionspacepointhash = :dimensionspacepointhash
            )
            select
                n.nodeaggregateid,
                n.origindimensionspacepoint,
                n.classification,
                n.nodetypename,
                n.properties,
                n.nodename,
                subtree_tags.tags
            from subgraph_root_path root_path, {$this->tableNames->hierarchyRelation()} h
                left join {$this->tableNames->node()} n
                    on n.relationanchorpoint = h.parentnodeanchor
                left join lateral (
                    with all_affected_subtrees as (
                        select *
                        from {$this->tableNames->subTreeRelation()} st
                        where n.nodeaggregateid = any (st.affected_nodeaggregateids)
                          and st.contentstreamid = :contentstreamid
                          and st.dimensionspacepointhash = :dimensionspacepointhash
                    )
                    select
                      -- Since there is no removal of tags down the inheritance chain,
                      -- we can simply add together all parent tags without having to look at the
                      -- inheritance chain order.
                      jsonb_build_object(
                        'explicit_tags', (select jsonb_agg(t.tag)
                                           from (select distinct unnest(expl_st.subtreetags)
                                                 from all_affected_subtrees expl_st
                                                 -- include only explicitly set tags
                                                 where expl_st.nodeaggregateid = n.nodeaggregateid) t(tag)
                             ),
                        'only_inherited', (select jsonb_agg(t.tag)
                                           from (select distinct unnest(expl_st.subtreetags)
                                                 from all_affected_subtrees expl_st
                                                 -- exclude explicitly set tags
                                                 where expl_st.nodeaggregateid != n.nodeaggregateid) t(tag))
                      ) as tags
                ) subtree_tags on true
            where h.parent_nodepath_absolute = '/' || root_path.nodename || '/' || :absolute_parent_path
              and n.nodename = :last_path_segment
              and h.contentstreamid = :contentstreamid
              and h.dimensionspacepointhash = :dimensionspacepointhash
                -- subtree tag filter
              and (
                  -- deactivate filter when no values are set
                  not :subtreetag_filter_active
                    or
                  not exists(
                    select 1
                    from {$this->tableNames->subTreeRelation()} st
                    where n.nodeaggregateid = any(st.affected_nodeaggregateids)
                      and st.dimensionspacepointhash = :dimensionspacepointhash
                      and st.contentstreamid = :contentstreamid
                      and st.subtreetags && array[:excluded_subtreetags]::varchar(36)[]
                  )
              )
        SQL;

        $result = $this->dbal->executeQuery($query, $parameters, $parameterTypes);
        $nodeRow = $result->fetchAssociative();

        if ($nodeRow === false) {
            return null;
        }

        $subtreeTags = json_decode($nodeRow['tags'], true);
        return Node::create(
            $this->contentRepositoryId,
            $this->workspaceName,
            $this->dimensionSpacePoint,
            NodeAggregateId::fromString($nodeRow['nodeaggreagteid']),
            OriginDimensionSpacePoint::fromJsonString($nodeRow['origindimensionspacepoint']),
            NodeAggregateClassification::from($nodeRow['classification']),
            NodeTypeName::fromString($nodeRow['nodetypename']),
            new PropertyCollection(
                SerializedPropertyValues::fromJsonString($nodeRow['properties']),
                $this->propertyConverter
            ),
            !empty($nodeRow['nodename']) ? NodeName::fromString($nodeRow['nodename']) : null,
            NodeTags::create(
                SubtreeTags::fromStrings(...($subtreeTags['explicit_tags'] ?? [])),
                SubtreeTags::fromStrings(...($subtreeTags['only_inherited'] ?? [])),
            ),
            Timestamps::create(
            // TODO replace with $nodeRow['created'] and $nodeRow['originalcreated'] once projection has implemented support
                QueryUtility::parseDateTimeString('2023-03-17 12:00:00'),
                QueryUtility::parseDateTimeString('2023-03-17 12:00:00'),
                null,
                null,
            ),
            $this->visibilityConstraints,
        );

    }*/

    public function findNodeByPath(NodePath|NodeName $path, NodeAggregateId $startingNodeAggregateId): ?Node
    {
        $path = $path instanceof NodeName ? NodePath::fromNodeNames($path) : $path;

        $startingNode = $this->findNodeById($startingNodeAggregateId);

        return $startingNode
            ? $this->findNodeByPathFromStartingNode($path, $startingNode)
            : null;
    }

    public function findNodeByAbsolutePath(AbsoluteNodePath $path): ?Node
    {
        $startingNode = $this->findRootNodeByType($path->rootNodeTypeName);

        return $startingNode
            ? $this->findNodeByPathFromStartingNode($path->path, $startingNode)
            : null;
    }

    private function findChildNodeConnectedThroughEdgeName(
        NodeAggregateId $parentNodeAggregateId,
        NodeName $nodeName
    ): ?Node {
        $query = HypergraphChildQuery::create(
            $this->contentStreamId,
            $parentNodeAggregateId,
            $this->tableNames,
        );
        $query = $query->withDimensionSpacePoint($this->dimensionSpacePoint)
            ->withRestriction($this->visibilityConstraints)
            ->withChildNodeName($nodeName);

        $nodeRow = $query->execute($this->dbal)->fetchAssociative();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $this->workspaceName,
            $this->visibilityConstraints,
            $this->dimensionSpacePoint,
        ) : null;
    }

    public function findSucceedingSiblingNodes(
        NodeAggregateId $siblingNodeAggregateId,
        FindSucceedingSiblingNodesFilter $filter
    ): Nodes {
        return $this->findAnySiblings(
            $siblingNodeAggregateId,
            HypergraphSiblingQueryMode::MODE_ONLY_SUCCEEDING,
            $filter->nodeTypes,
            $filter->pagination,
        );
    }

    public function findPrecedingSiblingNodes(
        NodeAggregateId $siblingNodeAggregateId,
        FindPrecedingSiblingNodesFilter $filter
    ): Nodes {
        return $this->findAnySiblings(
            $siblingNodeAggregateId,
            HypergraphSiblingQueryMode::MODE_ONLY_PRECEDING,
            $filter->nodeTypes,
            $filter->pagination,
        );
    }

    private function findAnySiblings(
        NodeAggregateId $sibling,
        HypergraphSiblingQueryMode $mode,
        ?NodeTypeCriteria $nodeTypeCriteria = null,
        ?Pagination $pagination = null,
    ): Nodes {
        $query = HypergraphSiblingQuery::create(
            $this->contentStreamId,
            $this->dimensionSpacePoint,
            $sibling,
            $mode,
            $this->tableNames
        );
        $query = $query->withRestriction($this->visibilityConstraints);
        if (!is_null($nodeTypeCriteria)) {
            $expandedNodeTypeCriteria = ExpandedNodeTypeCriteria::create(
                $nodeTypeCriteria,
                $this->nodeTypeManager
            );
            $query = $query->withNodeTypeCriteria($expandedNodeTypeCriteria, 'sn');
        }
        $query = $query->withOrdinalityOrdering($mode->isOrderingToBeReversed());
        if (!is_null($pagination)) {
            $query = $query
                ->withLimit($pagination->limit)
                ->withOffset($pagination->offset);
        }

        $siblingsRows = $query->execute($this->dbal)->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodes($siblingsRows, $this->workspaceName, $this->visibilityConstraints);
    }

    public function retrieveNodePath(NodeAggregateId $nodeAggregateId): AbsoluteNodePath
    {
        $leafNode = $this->findNodeById($nodeAggregateId);
        if (!$leafNode) {
            throw new \InvalidArgumentException(
                'Failed to retrieve node path for node "' . $nodeAggregateId->value . '"',
                1687513836
            );
        }
        $ancestors = $this->findAncestorNodes($leafNode->aggregateId, Filter\FindAncestorNodesFilter::create())
            ->reverse();

        try {
            return AbsoluteNodePath::fromLeafNodeAndAncestors($leafNode, $ancestors);
        } catch (\InvalidArgumentException $exception) {
            throw new \InvalidArgumentException(
                'Failed to retrieve node path for node "' . $nodeAggregateId->value . '"',
                1687513836,
                $exception
            );
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function findSubtree(
        NodeAggregateId $entryNodeAggregateId,
        FindSubtreeFilter $filter
    ): ?Subtree {
        $parameters = [
            'nodeaggregateid' => $entryNodeAggregateId->value,
            'contentstreamid' => $this->contentStreamId->value,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->hash,
        ];

        $parameterTypes = [
            'excluded_subtreetags' => Connection::PARAM_STR_ARRAY,
        ];

        $maximumLevelsClause = '';
        if ($filter->maximumLevels !== null) {
            $maximumLevelsClause = 'AND p.level < :maximumLevels';
            $parameters['maximumLevels'] = $filter->maximumLevels;
        }

        // subtree tag visibility filter
        $visibilityClause = '';
        if ($this->excludedSubtreeTagsFilterActive) {
            $visibilityClause = "AND NOT jsonb_exists_any(COALESCE(h.subtreetags->(n.relationanchorpoint::text), '{}'), array[:excluded_subtreetags]::text[])";
            $parameters['excluded_subtreetags'] = $this->excludedSubtreeTags;
        }

        $childVisibilityClause = '';
        if ($this->excludedSubtreeTagsFilterActive) {
            $childVisibilityClause = "AND NOT jsonb_exists_any(COALESCE(ch.subtreetags->(c.relationanchorpoint::text), '{}'), array[:excluded_subtreetags]::text[])";
        }

        $query = <<<SQL
            WITH RECURSIVE tree(
                nodeaggregateid, relationanchorpoint, origindimensionspacepoint,
                origindimensionspacepointhash, nodetypename, properties, classification,
                nodename, parentnodeaggregateid, level, position, subtreetags
            ) AS (
                -- Initial: entry node
                SELECT
                    n.nodeaggregateid, n.relationanchorpoint, n.origindimensionspacepoint,
                    n.origindimensionspacepointhash, n.nodetypename, n.properties, n.classification,
                    n.nodename,
                    'ROOT'::varchar AS parentnodeaggregateid,
                    0 AS level,
                    0 AS position,
                    h.subtreetags->(n.relationanchorpoint::text) AS subtreetags
                FROM {$this->tableNames->node()} n
                INNER JOIN {$this->tableNames->hierarchyRelation()} h
                    ON n.relationanchorpoint = ANY(h.childnodeanchors)
                WHERE n.nodeaggregateid = :nodeaggregateid
                    AND h.contentstreamid = :contentstreamid
                    AND h.dimensionspacepointhash = :dimensionspacepointhash
                    {$visibilityClause}

                UNION ALL

                -- Recursive: children
                SELECT
                    c.nodeaggregateid, c.relationanchorpoint, c.origindimensionspacepoint,
                    c.origindimensionspacepointhash, c.nodetypename, c.properties, c.classification,
                    c.nodename,
                    p.nodeaggregateid AS parentnodeaggregateid,
                    p.level + 1 AS level,
                    child_ord.ordinality::int AS position,
                    ch.subtreetags->(c.relationanchorpoint::text) AS subtreetags
                FROM tree p
                INNER JOIN {$this->tableNames->hierarchyRelation()} ch
                    ON ch.parentnodeanchor = p.relationanchorpoint
                    AND ch.contentstreamid = :contentstreamid
                    AND ch.dimensionspacepointhash = :dimensionspacepointhash
                CROSS JOIN LATERAL unnest(ch.childnodeanchors) WITH ORDINALITY AS child_ord(anchor, ordinality)
                INNER JOIN {$this->tableNames->node()} c
                    ON c.relationanchorpoint = child_ord.anchor
                WHERE true
                    {$maximumLevelsClause}
                    {$childVisibilityClause}
            )
            SELECT * FROM tree
            ORDER BY level, position
        SQL;

        $result = $this->dbal->executeQuery($query, $parameters, $parameterTypes);
        $rows = $result->fetchAllAssociative();
        if (empty($rows)) {
            return null;
        }

        /** @var array<string, Subtree[]> $subtreesByParentNodeId */
        $subtreesByParentNodeId = [];
        foreach (array_reverse($rows) as $nodeRow) {
            $nodeAggregateId = $nodeRow['nodeaggregateid'];
            $parentNodeAggregateId = $nodeRow['parentnodeaggregateid'];
            $node = Node::create(
                $this->contentRepositoryId,
                $this->workspaceName,
                $this->dimensionSpacePoint,
                NodeAggregateId::fromString($nodeAggregateId),
                OriginDimensionSpacePoint::fromJsonString($nodeRow['origindimensionspacepoint']),
                NodeAggregateClassification::from($nodeRow['classification']),
                NodeTypeName::fromString($nodeRow['nodetypename']),
                new PropertyCollection(
                    SerializedPropertyValues::fromJsonString($nodeRow['properties']),
                    $this->propertyConverter
                ),
                !empty($nodeRow['nodename']) ? NodeName::fromString($nodeRow['nodename']) : null,
                NodeFactory::extractNodeTagsFromJson($nodeRow['subtreetags'] ?? null),
                Timestamps::create(
                    // TODO replace with $nodeRow['created'] and $nodeRow['originalcreated'] once projection has implemented support
                    QueryUtility::parseDateTimeString('2023-03-17 12:00:00'),
                    QueryUtility::parseDateTimeString('2023-03-17 12:00:00'),
                    null,
                    null,
                ),
                $this->visibilityConstraints,
            );
            $level = (int)$nodeRow['level'];
            $subtree = Subtree::create(
                $level,
                $node,
                array_key_exists($nodeAggregateId, $subtreesByParentNodeId) ?
                    Subtrees::fromArray(array_reverse($subtreesByParentNodeId[$nodeAggregateId])) :
                    Subtrees::createEmpty()
            );
            if ($subtree->level === 0) {
                return $subtree;
            }
            if (!array_key_exists($parentNodeAggregateId, $subtreesByParentNodeId)) {
                $subtreesByParentNodeId[$parentNodeAggregateId] = [];
            }
            $subtreesByParentNodeId[$parentNodeAggregateId][] = $subtree;
        }

        return null;
    }

    public function findAncestorNodes(
        NodeAggregateId $entryNodeAggregateId,
        Filter\FindAncestorNodesFilter $filter
    ): Nodes {
        $parameters = [
            'nodeaggregateid' => $entryNodeAggregateId->value,
            'contentstreamid' => $this->contentStreamId->value,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->hash,
        ];
        $parameterTypes = [];

        $initialVisibilityClause = QueryUtility::getRestrictionClause(
            $this->visibilityConstraints, $this->tableNames, 'p', $parameters, $parameterTypes
        );
        $childVisibilityClause = QueryUtility::getRestrictionClause(
            $this->visibilityConstraints, $this->tableNames, 'c', $parameters, $parameterTypes
        );
        $recursiveVisibilityClause = QueryUtility::getRestrictionClause(
            $this->visibilityConstraints, $this->tableNames, '', $parameters, $parameterTypes
        );

        $nodeTypeCriteria = '';
        if ($filter->nodeTypes !== null) {
            $expandedNodeTypeCriteria = ExpandedNodeTypeCriteria::create(
                $filter->nodeTypes,
                $this->nodeTypeManager
            );
            $nodeTypeCriteria = QueryUtility::getNodeTypeCriteriaClause(
                $expandedNodeTypeCriteria, 'a', $parameters, $parameterTypes
            );
        }

        $query = /** @lang PostgreSQL */ <<<SQL
            WITH RECURSIVE ancestry(
                nodeaggregateid, relationanchorpoint, origindimensionspacepoint,
                origindimensionspacepointhash, nodetypename, properties, classification,
                nodename, subtreetags, parentnodeanchor, dimensionspacepoint, level
            ) AS (
                -- Initial: find the direct parent of the entry node
                SELECT
                    pn.nodeaggregateid, pn.relationanchorpoint, pn.origindimensionspacepoint,
                    pn.origindimensionspacepointhash, pn.nodetypename, pn.properties, pn.classification,
                    pn.nodename,
                    ph.subtreetags->(pn.relationanchorpoint::text) AS subtreetags,
                    ph.parentnodeanchor,
                    ph.dimensionspacepoint,
                    0 AS level
                FROM {$this->tableNames->node()} cn
                INNER JOIN {$this->tableNames->hierarchyRelation()} ch
                    ON cn.relationanchorpoint = ANY(ch.childnodeanchors)
                    AND ch.contentstreamid = :contentstreamid
                    AND ch.dimensionspacepointhash = :dimensionspacepointhash
                INNER JOIN {$this->tableNames->node()} pn
                    ON pn.relationanchorpoint = ch.parentnodeanchor
                INNER JOIN {$this->tableNames->hierarchyRelation()} ph
                    ON pn.relationanchorpoint = ANY(ph.childnodeanchors)
                    AND ph.contentstreamid = :contentstreamid
                    AND ph.dimensionspacepointhash = :dimensionspacepointhash
                WHERE cn.nodeaggregateid = :nodeaggregateid
                    {$initialVisibilityClause}
                    {$childVisibilityClause}

                UNION ALL

                -- Recursive: walk up via parentnodeanchor
                SELECT
                    n.nodeaggregateid, n.relationanchorpoint, n.origindimensionspacepoint,
                    n.origindimensionspacepointhash, n.nodetypename, n.properties, n.classification,
                    n.nodename,
                    h.subtreetags->(n.relationanchorpoint::text) AS subtreetags,
                    h.parentnodeanchor,
                    h.dimensionspacepoint,
                    prev.level + 1 AS level
                FROM ancestry prev
                INNER JOIN {$this->tableNames->node()} n
                    ON n.relationanchorpoint = prev.parentnodeanchor
                INNER JOIN {$this->tableNames->hierarchyRelation()} h
                    ON n.relationanchorpoint = ANY(h.childnodeanchors)
                    AND h.contentstreamid = :contentstreamid
                    AND h.dimensionspacepointhash = :dimensionspacepointhash
                WHERE true
                    {$recursiveVisibilityClause}
            )
            SELECT * FROM ancestry a
            WHERE true {$nodeTypeCriteria}
            ORDER BY level
        SQL;

        $result = $this->dbal->executeQuery($query, $parameters, $parameterTypes);
        $rows = $result->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodes(
            $rows,
            $this->workspaceName,
            $this->visibilityConstraints
        );
    }

    public function countAncestorNodes(
        NodeAggregateId $entryNodeAggregateId,
        Filter\CountAncestorNodesFilter $filter
    ): int {
        $parameters = [
            'nodeaggregateid' => $entryNodeAggregateId->value,
            'contentstreamid' => $this->contentStreamId->value,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->hash,
        ];
        $parameterTypes = [];

        $initialVisibilityClause = QueryUtility::getRestrictionClause(
            $this->visibilityConstraints, $this->tableNames, 'p', $parameters, $parameterTypes
        );
        $childVisibilityClause = QueryUtility::getRestrictionClause(
            $this->visibilityConstraints, $this->tableNames, 'c', $parameters, $parameterTypes
        );
        $recursiveVisibilityClause = QueryUtility::getRestrictionClause(
            $this->visibilityConstraints, $this->tableNames, '', $parameters, $parameterTypes
        );

        $nodeTypeCriteria = '';
        if ($filter->nodeTypes !== null) {
            $expandedNodeTypeCriteria = ExpandedNodeTypeCriteria::create(
                $filter->nodeTypes,
                $this->nodeTypeManager
            );
            $nodeTypeCriteria = QueryUtility::getNodeTypeCriteriaClause(
                $expandedNodeTypeCriteria, 'a', $parameters, $parameterTypes
            );
        }

        $query = /** @lang PostgreSQL */ <<<SQL
            WITH RECURSIVE ancestry(
                nodeaggregateid, relationanchorpoint, origindimensionspacepoint,
                origindimensionspacepointhash, nodetypename, properties, classification,
                nodename, subtreetags, parentnodeanchor, dimensionspacepoint, level
            ) AS (
                SELECT
                    pn.nodeaggregateid, pn.relationanchorpoint, pn.origindimensionspacepoint,
                    pn.origindimensionspacepointhash, pn.nodetypename, pn.properties, pn.classification,
                    pn.nodename,
                    ph.subtreetags->(pn.relationanchorpoint::text) AS subtreetags,
                    ph.parentnodeanchor,
                    ph.dimensionspacepoint,
                    0 AS level
                FROM {$this->tableNames->node()} cn
                INNER JOIN {$this->tableNames->hierarchyRelation()} ch
                    ON cn.relationanchorpoint = ANY(ch.childnodeanchors)
                    AND ch.contentstreamid = :contentstreamid
                    AND ch.dimensionspacepointhash = :dimensionspacepointhash
                INNER JOIN {$this->tableNames->node()} pn
                    ON pn.relationanchorpoint = ch.parentnodeanchor
                INNER JOIN {$this->tableNames->hierarchyRelation()} ph
                    ON pn.relationanchorpoint = ANY(ph.childnodeanchors)
                    AND ph.contentstreamid = :contentstreamid
                    AND ph.dimensionspacepointhash = :dimensionspacepointhash
                WHERE cn.nodeaggregateid = :nodeaggregateid
                    {$initialVisibilityClause}
                    {$childVisibilityClause}

                UNION ALL

                SELECT
                    n.nodeaggregateid, n.relationanchorpoint, n.origindimensionspacepoint,
                    n.origindimensionspacepointhash, n.nodetypename, n.properties, n.classification,
                    n.nodename,
                    h.subtreetags->(n.relationanchorpoint::text) AS subtreetags,
                    h.parentnodeanchor,
                    h.dimensionspacepoint,
                    prev.level + 1 AS level
                FROM ancestry prev
                INNER JOIN {$this->tableNames->node()} n
                    ON n.relationanchorpoint = prev.parentnodeanchor
                INNER JOIN {$this->tableNames->hierarchyRelation()} h
                    ON n.relationanchorpoint = ANY(h.childnodeanchors)
                    AND h.contentstreamid = :contentstreamid
                    AND h.dimensionspacepointhash = :dimensionspacepointhash
                WHERE true
                    {$recursiveVisibilityClause}
            )
            SELECT COUNT(*) FROM ancestry a
            WHERE true {$nodeTypeCriteria}
        SQL;

        $result = $this->dbal->executeQuery($query, $parameters, $parameterTypes);
        return (int)$result->fetchOne();
    }

    public function findClosestNode(
        NodeAggregateId $entryNodeAggregateId,
        FindClosestNodeFilter $filter
    ): ?Node {
        $parameters = [
            'nodeaggregateid' => $entryNodeAggregateId->value,
            'contentstreamid' => $this->contentStreamId->value,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->hash,
        ];
        $parameterTypes = [];

        $visibilityClause = QueryUtility::getRestrictionClause(
            $this->visibilityConstraints, $this->tableNames, '', $parameters, $parameterTypes
        );

        $expandedNodeTypeCriteria = ExpandedNodeTypeCriteria::create(
            $filter->nodeTypes,
            $this->nodeTypeManager
        );
        $nodeTypeCriteria = QueryUtility::getNodeTypeCriteriaClause(
            $expandedNodeTypeCriteria, 'a', $parameters, $parameterTypes
        );

        $query = /** @lang PostgreSQL */ <<<SQL
            WITH RECURSIVE ancestry(
                nodeaggregateid, relationanchorpoint, origindimensionspacepoint,
                origindimensionspacepointhash, nodetypename, properties, classification,
                nodename, subtreetags, parentnodeanchor, dimensionspacepoint, level
            ) AS (
                -- Initial: the entry node itself
                SELECT
                    n.nodeaggregateid, n.relationanchorpoint, n.origindimensionspacepoint,
                    n.origindimensionspacepointhash, n.nodetypename, n.properties, n.classification,
                    n.nodename,
                    h.subtreetags->(n.relationanchorpoint::text) AS subtreetags,
                    h.parentnodeanchor,
                    h.dimensionspacepoint,
                    0 AS level
                FROM {$this->tableNames->node()} n
                INNER JOIN {$this->tableNames->hierarchyRelation()} h
                    ON n.relationanchorpoint = ANY(h.childnodeanchors)
                    AND h.contentstreamid = :contentstreamid
                    AND h.dimensionspacepointhash = :dimensionspacepointhash
                WHERE n.nodeaggregateid = :nodeaggregateid
                    {$visibilityClause}

                UNION ALL

                -- Recursive: walk up via parentnodeanchor
                SELECT
                    n.nodeaggregateid, n.relationanchorpoint, n.origindimensionspacepoint,
                    n.origindimensionspacepointhash, n.nodetypename, n.properties, n.classification,
                    n.nodename,
                    h.subtreetags->(n.relationanchorpoint::text) AS subtreetags,
                    h.parentnodeanchor,
                    h.dimensionspacepoint,
                    prev.level + 1 AS level
                FROM ancestry prev
                INNER JOIN {$this->tableNames->node()} n
                    ON n.relationanchorpoint = prev.parentnodeanchor
                INNER JOIN {$this->tableNames->hierarchyRelation()} h
                    ON n.relationanchorpoint = ANY(h.childnodeanchors)
                    AND h.contentstreamid = :contentstreamid
                    AND h.dimensionspacepointhash = :dimensionspacepointhash
                WHERE true
                    {$visibilityClause}
            )
            SELECT * FROM ancestry a
            WHERE true {$nodeTypeCriteria}
            ORDER BY level
            LIMIT 1
        SQL;

        $result = $this->dbal->executeQuery($query, $parameters, $parameterTypes);
        $nodeRow = $result->fetchAssociative();

        return $nodeRow ? $this->nodeFactory->mapNodeRowToNode(
            $nodeRow,
            $this->workspaceName,
            $this->visibilityConstraints,
            $this->dimensionSpacePoint,
        ) : null;
    }

    public function findDescendantNodes(
        NodeAggregateId $entryNodeAggregateId,
        FindDescendantNodesFilter $filter
    ): Nodes {
        $parameters = [
            'nodeaggregateid' => $entryNodeAggregateId->value,
            'contentstreamid' => $this->contentStreamId->value,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->hash,
        ];
        $parameterTypes = [];

        $childVisibilityClause = '';
        if ($this->excludedSubtreeTagsFilterActive) {
            $childVisibilityClause = "AND NOT jsonb_exists_any(COALESCE(ch.subtreetags->(c.relationanchorpoint::text), '{}'), ARRAY[:excludedSubtreeTags]::text[])";
            $parameters['excludedSubtreeTags'] = $this->excludedSubtreeTags;
            $parameterTypes['excludedSubtreeTags'] = Connection::PARAM_STR_ARRAY;
        }

        $nodeTypeCriteria = '';
        if ($filter->nodeTypes !== null) {
            $expandedNodeTypeCriteria = ExpandedNodeTypeCriteria::create(
                $filter->nodeTypes,
                $this->nodeTypeManager
            );
            $nodeTypeCriteria = QueryUtility::getNodeTypeCriteriaClause(
                $expandedNodeTypeCriteria, 't', $parameters, $parameterTypes
            );
        }

        $paginationClause = '';
        if ($filter->pagination !== null) {
            $paginationClause = 'LIMIT ' . $filter->pagination->limit . ' OFFSET ' . $filter->pagination->offset;
        }

        $query = /** @lang PostgreSQL */ <<<SQL
            WITH RECURSIVE tree(
                nodeaggregateid, relationanchorpoint, origindimensionspacepoint,
                origindimensionspacepointhash, nodetypename, properties, classification,
                nodename, subtreetags, dimensionspacepoint, level, position
            ) AS (
                -- Initial: direct children of the entry node
                SELECT
                    c.nodeaggregateid, c.relationanchorpoint, c.origindimensionspacepoint,
                    c.origindimensionspacepointhash, c.nodetypename, c.properties, c.classification,
                    c.nodename,
                    ch.subtreetags->(c.relationanchorpoint::text) AS subtreetags,
                    ch.dimensionspacepoint,
                    0 AS level,
                    child_ord.ordinality::int AS position
                FROM {$this->tableNames->node()} p
                INNER JOIN {$this->tableNames->hierarchyRelation()} ch
                    ON ch.parentnodeanchor = p.relationanchorpoint
                    AND ch.contentstreamid = :contentstreamid
                    AND ch.dimensionspacepointhash = :dimensionspacepointhash
                CROSS JOIN LATERAL unnest(ch.childnodeanchors) WITH ORDINALITY AS child_ord(anchor, ordinality)
                INNER JOIN {$this->tableNames->node()} c
                    ON c.relationanchorpoint = child_ord.anchor
                WHERE p.nodeaggregateid = :nodeaggregateid
                    {$childVisibilityClause}

                UNION ALL

                -- Recursive: children of children
                SELECT
                    c.nodeaggregateid, c.relationanchorpoint, c.origindimensionspacepoint,
                    c.origindimensionspacepointhash, c.nodetypename, c.properties, c.classification,
                    c.nodename,
                    ch.subtreetags->(c.relationanchorpoint::text) AS subtreetags,
                    ch.dimensionspacepoint,
                    prev.level + 1 AS level,
                    child_ord.ordinality::int AS position
                FROM tree prev
                INNER JOIN {$this->tableNames->hierarchyRelation()} ch
                    ON ch.parentnodeanchor = prev.relationanchorpoint
                    AND ch.contentstreamid = :contentstreamid
                    AND ch.dimensionspacepointhash = :dimensionspacepointhash
                CROSS JOIN LATERAL unnest(ch.childnodeanchors) WITH ORDINALITY AS child_ord(anchor, ordinality)
                INNER JOIN {$this->tableNames->node()} c
                    ON c.relationanchorpoint = child_ord.anchor
                WHERE true
                    {$childVisibilityClause}
            )
            SELECT * FROM tree t
            WHERE true {$nodeTypeCriteria}
            ORDER BY level, position
            {$paginationClause}
        SQL;

        $result = $this->dbal->executeQuery($query, $parameters, $parameterTypes);
        $rows = $result->fetchAllAssociative();

        return $this->nodeFactory->mapNodeRowsToNodes(
            $rows,
            $this->workspaceName,
            $this->visibilityConstraints
        );
    }

    public function countDescendantNodes(NodeAggregateId $entryNodeAggregateId, Filter\CountDescendantNodesFilter $filter): int
    {
        $parameters = [
            'nodeaggregateid' => $entryNodeAggregateId->value,
            'contentstreamid' => $this->contentStreamId->value,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->hash,
        ];
        $parameterTypes = [];

        $childVisibilityClause = '';
        if ($this->excludedSubtreeTagsFilterActive) {
            $childVisibilityClause = "AND NOT jsonb_exists_any(COALESCE(ch.subtreetags->(c.relationanchorpoint::text), '{}'), ARRAY[:excludedSubtreeTags]::text[])";
            $parameters['excludedSubtreeTags'] = $this->excludedSubtreeTags;
            $parameterTypes['excludedSubtreeTags'] = Connection::PARAM_STR_ARRAY;
        }

        $nodeTypeCriteria = '';
        if ($filter->nodeTypes !== null) {
            $expandedNodeTypeCriteria = ExpandedNodeTypeCriteria::create(
                $filter->nodeTypes,
                $this->nodeTypeManager
            );
            $nodeTypeCriteria = QueryUtility::getNodeTypeCriteriaClause(
                $expandedNodeTypeCriteria, 't', $parameters, $parameterTypes
            );
        }

        $query = /** @lang PostgreSQL */ <<<SQL
            WITH RECURSIVE tree(
                nodeaggregateid, relationanchorpoint, origindimensionspacepoint,
                origindimensionspacepointhash, nodetypename, properties, classification,
                nodename, subtreetags, dimensionspacepoint, level, position
            ) AS (
                SELECT
                    c.nodeaggregateid, c.relationanchorpoint, c.origindimensionspacepoint,
                    c.origindimensionspacepointhash, c.nodetypename, c.properties, c.classification,
                    c.nodename,
                    ch.subtreetags->(c.relationanchorpoint::text) AS subtreetags,
                    ch.dimensionspacepoint,
                    0 AS level,
                    child_ord.ordinality::int AS position
                FROM {$this->tableNames->node()} p
                INNER JOIN {$this->tableNames->hierarchyRelation()} ch
                    ON ch.parentnodeanchor = p.relationanchorpoint
                    AND ch.contentstreamid = :contentstreamid
                    AND ch.dimensionspacepointhash = :dimensionspacepointhash
                CROSS JOIN LATERAL unnest(ch.childnodeanchors) WITH ORDINALITY AS child_ord(anchor, ordinality)
                INNER JOIN {$this->tableNames->node()} c
                    ON c.relationanchorpoint = child_ord.anchor
                WHERE p.nodeaggregateid = :nodeaggregateid
                    {$childVisibilityClause}

                UNION ALL

                SELECT
                    c.nodeaggregateid, c.relationanchorpoint, c.origindimensionspacepoint,
                    c.origindimensionspacepointhash, c.nodetypename, c.properties, c.classification,
                    c.nodename,
                    ch.subtreetags->(c.relationanchorpoint::text) AS subtreetags,
                    ch.dimensionspacepoint,
                    prev.level + 1 AS level,
                    child_ord.ordinality::int AS position
                FROM tree prev
                INNER JOIN {$this->tableNames->hierarchyRelation()} ch
                    ON ch.parentnodeanchor = prev.relationanchorpoint
                    AND ch.contentstreamid = :contentstreamid
                    AND ch.dimensionspacepointhash = :dimensionspacepointhash
                CROSS JOIN LATERAL unnest(ch.childnodeanchors) WITH ORDINALITY AS child_ord(anchor, ordinality)
                INNER JOIN {$this->tableNames->node()} c
                    ON c.relationanchorpoint = child_ord.anchor
                WHERE true
                    {$childVisibilityClause}
            )
            SELECT COUNT(*) FROM tree t
            WHERE true {$nodeTypeCriteria}
        SQL;

        $result = $this->dbal->executeQuery($query, $parameters, $parameterTypes);
        return (int)$result->fetchOne();
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function countNodes(): int
    {
        $query = /** @lang PostgreSQL */
        'SELECT COUNT(*)
            FROM ' . $this->tableNames->hierarchyRelation() . ' h
            JOIN ' . $this->tableNames->node() . ' n ON n.relationanchorpoint = ANY(h.childnodeanchors)
            WHERE h.contentstreamid = :contentStreamId
            AND h.dimensionspacepointhash = :dimensionSpacePointHash';

        $parameters = [
            'contentStreamId' => $this->contentStreamId->value,
            'dimensionSpacePointHash' => $this->dimensionSpacePoint->hash
        ];

        $result = $this->dbal->executeQuery($query, $parameters)->fetchNumeric();

        return $result ? $result[0] : 0;
    }

    private function findNodeByPathFromStartingNode(NodePath $path, Node $startingNode): ?Node
    {
        $currentNode = $startingNode;
        foreach ($path->getParts() as $edgeName) {
            // id exists here :)
            $currentNode = $this->findChildNodeConnectedThroughEdgeName($currentNode->aggregateId, $edgeName);
            if ($currentNode === null) {
                return null;
            }
        }
        return $currentNode;
    }
}
