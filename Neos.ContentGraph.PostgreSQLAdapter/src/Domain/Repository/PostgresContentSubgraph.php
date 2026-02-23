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
use Doctrine\DBAL\Types\Types;
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
use Neos\ContentRepository\Core\Feature\NodeCreation\NodeCreation;
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

    private const DEFAULT_CHILD_NODE_LIMIT = 100000;
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
        // FIXME lets think about adding all inherited node types as column to the DB, this would move
        //       the calculation heavy part to the write side (which I assume happens a lot less than actual reading)
        if ($filter->nodeTypes !== null) {
            $expandedNodeTypeCriteria = ExpandedNodeTypeCriteria::create(
                $filter->nodeTypes,
                $this->nodeTypeManager
            );
        } else {
            $expandedNodeTypeCriteria = null;
        }

        $nonEmptyNodeTypeFilter = $expandedNodeTypeCriteria !== null &&
            (!$expandedNodeTypeCriteria->explicitlyAllowedNodeTypeNames->isEmpty()
                || !$expandedNodeTypeCriteria->explicitlyDisallowedNodeTypeNames->isEmpty());

        $parameters = [
            'parentnodeaggregateid' => $parentNodeAggregateId->value,
            'contentstreamid' => $this->contentStreamId->value,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->hash,
            'result_offset' => $filter->pagination?->offset ?? 0,
            'result_limit' => $filter->pagination?->limit ?? self::DEFAULT_CHILD_NODE_LIMIT,
            'subtreetag_filter_active' => $this->excludedSubtreeTagsFilterActive,
            'excluded_subtreetags' => $this->excludedSubtreeTags,
            'mode_wildcard_both' => $nonEmptyNodeTypeFilter && $expandedNodeTypeCriteria->isWildCardAllowed
                && !$expandedNodeTypeCriteria->explicitlyAllowedNodeTypeNames->isEmpty()
                && !$expandedNodeTypeCriteria->explicitlyDisallowedNodeTypeNames->isEmpty(),
            'mode_no_wildcard_both' => $nonEmptyNodeTypeFilter && !$expandedNodeTypeCriteria->isWildCardAllowed
                && !$expandedNodeTypeCriteria->explicitlyAllowedNodeTypeNames->isEmpty()
                && !$expandedNodeTypeCriteria->explicitlyDisallowedNodeTypeNames->isEmpty(),
            'mode_no_wildcard_only_allowed' => $nonEmptyNodeTypeFilter && !$expandedNodeTypeCriteria->isWildCardAllowed
                && $expandedNodeTypeCriteria->explicitlyDisallowedNodeTypeNames->isEmpty(),
            'mode_only_disallowed' => $nonEmptyNodeTypeFilter
                && $expandedNodeTypeCriteria->explicitlyAllowedNodeTypeNames->isEmpty(),
            'nodetype_allowed' => $expandedNodeTypeCriteria?->explicitlyAllowedNodeTypeNames ?? [],
            'nodetype_disallowed' => $expandedNodeTypeCriteria?->explicitlyDisallowedNodeTypeNames ?? [],
        ];

        $parameterTypes = [
            'excluded_subtreetags' => Connection::PARAM_STR_ARRAY,
            'nodetype_allowed' => Connection::PARAM_STR_ARRAY,
            'nodetype_disallowed' => Connection::PARAM_STR_ARRAY,
            'mode_wildcard_both' => Types::BOOLEAN,
            'mode_no_wildcard_both' => Types::BOOLEAN,
            'mode_no_wildcard_only_allowed' => Types::BOOLEAN,
            'mode_only_disallowed' => Types::BOOLEAN,
        ];

        // TODO hier weiter (filter)

        $query = <<<SQL
            with parent as (
                select {$this->tableNames->functionGetRelationAnchorPoint()}(
                        :parentnodeaggregateid,
                        :contentstreamid,
                        :dimensionspacepointhash
                    ) as parentnodeanchor
            ),
            child_node_anchors as (
                select
                    cna.*,
                    ph.dimensionspacepoint,
                    ph.dimensionspacepointhash
                from {$this->tableNames->hierarchyRelation()} ph, parent pna
                    -- with this join, we keep the order of the child nodes from inside the array
                    left join lateral (
                        select
                            *
                        from unnest(ph.childnodeanchors) with ordinality as children(childnodeanchor, position)
                    ) cna on true
                -- parent node anchor == null means root node
                where ph.parentnodeanchor = coalesce(pna.parentnodeanchor, 0)
                  and ph.contentstreamid = :contentstreamid
                  and ph.dimensionspacepointhash = :dimensionspacepointhash
            )
            select
                cn.nodeaggregateid,
                cn.origindimensionspacepoint,
                cn.nodetypename,
                cn.nodename,
                cn.properties,
                cn.classification,
                -- subtreetags
                subtree_tags.tags
            from child_node_anchors cna
                left join {$this->tableNames->node()} cn
                    on cn.relationanchorpoint = cna.childnodeanchor
                left join lateral (
                    with all_affected_subtrees as (
                        select *
                        from {$this->tableNames->subTreeRelation()} st
                        where cn.nodeaggregateid = any (st.affected_nodeaggregateids)
                          and st.contentstreamid = :contentstreamid
                          and st.dimensionspacepointhash = cna.dimensionspacepointhash
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
                                                 where expl_st.nodeaggregateid = cn.nodeaggregateid) t(tag)
                             ),
                        'only_inherited', (select jsonb_agg(t.tag)
                                           from (select distinct unnest(expl_st.subtreetags)
                                                 from all_affected_subtrees expl_st
                                                 -- exclude explicitly set tags
                                                 where expl_st.nodeaggregateid != cn.nodeaggregateid) t(tag))
                      ) as tags
                ) subtree_tags on true
            where
              -- subtree tag filtering
              (
                  -- deactivate filter when no values are set
                  not :subtreetag_filter_active
                    or
                  not exists(
                    select 1
                    from {$this->tableNames->subTreeRelation()} st
                    where cn.nodeaggregateid = any(st.affected_nodeaggregateids)
                      and st.dimensionspacepointhash = :dimensionspacepointhash
                      and st.contentstreamid = :contentstreamid
                      and st.subtreetags && array[:excluded_subtreetags]::varchar(36)[]
                  )
              )
              -- node type filtering
              and
                ( not :mode_wildcard_both or
                  (cn.nodetypename not in (:nodetype_disallowed)
                      or cn.nodetypename in (:nodetype_allowed))
                )
                and
                ( not :mode_no_wildcard_both or
                  (cn.nodetypename not in (:nodetype_disallowed)
                      and cn.nodetypename in (:nodetype_allowed))
                )
                and
                ( not :mode_no_wildcard_only_allowed or
                  cn.nodetypename in (:nodetype_allowed)
                )
                and
                ( not :mode_only_disallowed or
                  cn.nodetypename not in (:nodetype_disallowed)
                )
            order by cna.position
            limit :result_limit
            offset :result_offset
        SQL;

        $result = $this->dbal->executeQuery($query, $parameters, $parameterTypes);
        $rows = $result->fetchAllAssociative();


        $nodesDeserialized = [];
        foreach ($rows as $nodeRow) {
            $subtreeTags = json_decode($nodeRow['tags'], true);
            $nodesDeserialized[] = Node::create(
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
        }
        return Nodes::fromArray($nodesDeserialized);
    }

    public function countChildNodes(NodeAggregateId $parentNodeAggregateId, Filter\CountChildNodesFilter $filter): int
    {
        // TODO: Implement countChildNodes() method.
        return 0;
    }

    public function findReferences(
        NodeAggregateId $nodeAggregateId,
        FindReferencesFilter $filter
    ): References {
        $parameters = [
            // filters from ContentSubgraph
            'contentstreamid' => $this->contentStreamId->value,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->hash,
            'subtreetag_filter_active' => $this->excludedSubtreeTagsFilterActive,
            'excluded_subtreetags' => $this->excludedSubtreeTags,
            // filters from parameter input
            'nodeaggregateid' => $nodeAggregateId->value
        ];
        $parameterTypes = [
            'excluded_subtreetags' => Connection::PARAM_STR_ARRAY
        ];

        $query = <<<SQL
            with source_anchor as (
                select {$this->tableNames->functionGetRelationAnchorPoint()}(
                    :nodeaggregateid,
                    :contentstreamid,
                    :dimensionspacepointhash
                ) as relationanchorpoint
            )
            select
                rr.name as reference_name,
                rr.properties as reference_properties,
                target_n.nodeaggregateid as target_nodeaggregateid,
                target_n.origindimensionspacepoint as target_origindimensionspacepoint,
                target_n.classification as target_classification,
                target_n.nodetypename as target_nodetypename,
                target_n.nodename as target_nodename,
                target_n.properties as target_properties
            from source_anchor source, {$this->tableNames->referenceRelation()} rr
                left join {$this->tableNames->node()} target_n
                    on target_n.nodeaggregateid = rr.targetnodeaggregateid
                left join {$this->tableNames->hierarchyRelation()} target_h
                    on target_n.relationanchorpoint = ANY(target_h.childnodeanchors)
                   and target_h.contentstreamid = :contentstreamid
                   and target_h.dimensionspacepointhash = :dimensionspacepointhash
            where rr.sourcenodeanchor = source.relationanchorpoint
                -- subtree tag filter
            and (
                -- deactivate filter when no values are set
                not :subtreetag_filter_active
                  or
                not exists(
                  select 1
                  from {$this->tableNames->subTreeRelation()} st
                  where target_n.relationanchorpoint = any(st.affected_relationanchorpoints)
                    and st.dimensionspacepointhash = :dimensionspacepointhash
                    and st.contentstreamid = :contentstreamid
                    and st.subtreetags && array[:excluded_subtreetags]::varchar(36)[]
                )
            )
        SQL;

        $result = $this->dbal->executeQuery($query, $parameters, $parameterTypes);
        $rows = $result->fetchAllAssociative();

        $references = [];
        foreach ($rows as $referenceRow) {
            $referenceProperties = $referenceRow['reference_properties'];
            $references[] = new Reference(
                Node::create(
                    $this->contentRepositoryId,
                    $this->workspaceName,
                    $this->dimensionSpacePoint,
                    NodeAggregateId::fromString($referenceRow['target_nodeaggregateid']),
                    OriginDimensionSpacePoint::fromJsonString($referenceRow['target_origindimensionspacepoint']),
                    NodeAggregateClassification::from($referenceRow['target_classification']),
                    NodeTypeName::fromString($referenceRow['target_nodetypename']),
                    new PropertyCollection(
                        SerializedPropertyValues::fromJsonString($referenceRow['target_properties']),
                        $this->propertyConverter
                    ),
                    $referenceRow['target_nodename'] ? NodeName::fromString($referenceRow['target_nodename']) : null,
                    // TODO implement {@see \Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory::mapNodeRowToNode()}
                    NodeTags::createEmpty(),
                    Timestamps::create(
                    // TODO replace with $nodeRow['created'] and $nodeRow['originalcreated'] once projection has implemented support
                        QueryUtility::parseDateTimeString('2023-03-17 12:00:00'),
                        QueryUtility::parseDateTimeString('2023-03-17 12:00:00'),
                        null,
                        null,
                    ),
                    $this->visibilityConstraints,
                ),
                ReferenceName::fromString($referenceRow['reference_name']),
                $referenceProperties
                    ? new PropertyCollection(
                        SerializedPropertyValues::fromJsonString($referenceProperties),
                        $this->propertyConverter
                    )
                    : null
            );
        }

        return References::fromArray($references);
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
            'srcn.*, srch.contentstreamid, srch.dimensionspacepoint',
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

        // TODO node types filter

        $parameters = [
            // subgraph conditions
            'contentstreamid' => $this->contentStreamId->value,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->hash,
            'subtreetag_filter_active' => $this->excludedSubtreeTagsFilterActive,
            'excluded_subtreetags' => $this->excludedSubtreeTags,
            // parameter input conditions
            'sibling_nodeaggregateid' => $siblingNodeAggregateId->value,
            'result_offset' => $filter->pagination?->offset ?? 0,
            'result_limit' => $filter->pagination?->limit ?? self::DEFAULT_SIBLING_NODE_LIMIT,
        ];

        $parameterTypes = [
            'excluded_subtreetags' => Connection::PARAM_STR_ARRAY
        ];

        $query = <<<SQL
            with relation_anchor as (
                select {$this->tableNames->functionGetRelationAnchorPoint()}(
                    :sibling_nodeaggregateid,
                    :contentstreamid,
                    :dimensionspacepointhash
                ) as relationanchorpoint
            ),
            successor_anchors as (
                select
                  -- here, we only take the succeeding siblings
                    successors.*
                from cr_default_p_graph_hierarchyrelation h, relation_anchor ra,
                     unnest(h.childnodeanchors[(array_position(h.childnodeanchors, ra.relationanchorpoint)+1):])
                        with ordinality successors(relationanchorpoint, position_offset)
                where ra.relationanchorpoint = any(h.childnodeanchors)
                  and h.contentstreamid = :contentstreamid
                  and h.dimensionspacepointhash = :dimensionspacepointhash
                  -- subtree tag filtering
                  and (
                      -- deactivate filter when no values are set
                      not :subtreetag_filter_active
                        or
                      not exists(
                        select 1
                        from {$this->tableNames->subTreeRelation()} st
                        where successors.relationanchorpoint = any(st.affected_relationanchorpoints)
                          and st.dimensionspacepointhash = :dimensionspacepointhash
                          and st.contentstreamid = :contentstreamid
                          and st.subtreetags && array[:excluded_subtreetags]::varchar(36)[]
                      )
                  )
            )
            select
                n.nodeaggregateid,
                n.origindimensionspacepoint,
                n.nodetypename,
                n.nodename,
                n.properties,
                n.classification,
                subtree_tags.tags
            from successor_anchors succ_a
                left join {$this->tableNames->node()} n
                    on n.relationanchorpoint = succ_a.relationanchorpoint
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
            order by succ_a.position_offset
            limit :result_limit
            offset :result_offset
        SQL;

        $result = $this->dbal->executeQuery($query, $parameters, $parameterTypes);
        $rows = $result->fetchAllAssociative();


        $nodesDeserialized = [];
        foreach ($rows as $nodeRow) {
            $subtreeTags = json_decode($nodeRow['tags'], true);
            $nodesDeserialized[] = Node::create(
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
        }
        return Nodes::fromArray($nodesDeserialized);
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
        return AbsoluteNodePath::fromString('/<Neos.ContentRepository:Root>');
    }

    /**
     * Finds the Subtree for a given node aggregate ID.
     *
     * This operation is scalable, since we **always** lookup subtrees with the
     * primary key. The recursive calculation of the sub-tree itself happens purely on the write-side.
     *
     * @see NodeCreation (write-side)
     *
     * @param NodeAggregateId $entryNodeAggregateId the root node of the sub-tree to fetch
     * @param FindSubtreeFilter $filter the filter for excluding nodes from the subtree
     * @return Subtree|null the created {@link Subtree} instance or null, if there is no such a node in this Subgraph.
     *
     * @throws \Doctrine\DBAL\Exception on any database error
     */
    public function findSubtree(
        NodeAggregateId $entryNodeAggregateId,
        FindSubtreeFilter $filter
    ): ?Subtree {
        $parameters = [
            'nodeaggregateid' => $entryNodeAggregateId->value,
            'contentstreamid' => $this->contentStreamId->value,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->hash,
            'maximum_levels' => $filter->maximumLevels,
            'subtreetag_filter_active' => $this->excludedSubtreeTagsFilterActive,
            'excluded_subtreetags' => $this->excludedSubtreeTags,
        ];

        $parameterTypes = [
            'excluded_subtreetags' => Connection::PARAM_STR_ARRAY,
        ];

        // TODO nodetypes filter
        if ($filter->nodeTypes !== null) {
            $expandedNodeTypeCriteria = ExpandedNodeTypeCriteria::create(
                $filter->nodeTypes,
                $this->nodeTypeManager
            );
        } else {
        }

        $query = <<<SQL
            with subtree_entry as (
                select
                    st.affected_relationanchorpoints,
                    st.dimensionspacepoint,
                    st.subtree_structure
                from {$this->tableNames->subTreeRelation()} st
                where st.nodeaggregateid = :nodeaggregateid
                  and st.contentstreamid = :contentstreamid
                  and st.dimensionspacepointhash = :dimensionspacepointhash
            )
            select
                st.dimensionspacepoint,
                subtree_nodes.nodes
            from subtree_entry st
                left join lateral (
                    with nodes_of_subtree as (
                        select n.*,
                            st.subtree_structure -> n.nodeaggregateid ->> 'depth' as depth,
                            st.subtree_structure -> n.nodeaggregateid ->> 'parent' as parent
                        from unnest(st.affected_relationanchorpoints) affected_anchors(relationanchorpoint)
                            left join {$this->tableNames->node()} n
                                on n.relationanchorpoint = affected_anchors.relationanchorpoint
                        where
                            -- subtree tag filtering
                            (
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
                        order by st.subtree_structure -> n.nodeaggregateid ->> 'depth' desc,
                                     st.subtree_structure -> n.nodeaggregateid ->> 'ordinality'
                    )
                    select
                        jsonb_agg(jsonb_build_object(
                            -- 'relationanchorpoint', n.relationanchorpoint,
                            'nodeaggregateid', n.nodeaggregateid,
                            'parentnodeaggregateid', n.parent,
                            'origindimensionspacepoint', n.origindimensionspacepoint,
                            'nodetypename', n.nodetypename,
                            'nodename', n.nodename,
                            'properties', n.properties,
                            'classification', n.classification,
                            'depth', n.depth
                        )) as nodes
                    from nodes_of_subtree n
                ) subtree_nodes on true
        SQL;

        $result = $this->dbal->executeQuery($query, $parameters, $parameterTypes);
        $resultRow = $result->fetchAssociative();
        if ($resultRow === false) {
            return null;
        }
        $nodesArray = json_decode($resultRow['nodes'], true);
        if (!is_array($nodesArray) || empty($nodesArray)) {
            return null;
        }

        // we have results
        $dimensionSpacePoint = DimensionSpacePoint::fromJsonString($resultRow['dimensionspacepoint']);

        /** @var array<string, Subtree[]> $subtreesByParentNodeId */
        $subtreesByParentNodeId = [];
        foreach ($nodesArray as $nodeJson) {
            $parentNodeAggregateId = $nodeJson['parentnodeaggregateid'];
            $node = Node::create(
                $this->contentRepositoryId,
                $this->workspaceName,
                $dimensionSpacePoint,
                NodeAggregateId::fromString($nodeJson['nodeaggregateid']),
                OriginDimensionSpacePoint::fromArray($nodeJson['origindimensionspacepoint']),
                NodeAggregateClassification::from($nodeJson['classification']),
                NodeTypeName::fromString($nodeJson['nodetypename']),
                new PropertyCollection(
                    SerializedPropertyValues::fromArray($nodeJson['properties']),
                    $this->propertyConverter
                ),
                $nodeJson['nodename'] ? NodeName::fromString($nodeJson['nodename']) : null,
                // TODO implement {@see \Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory::mapNodeRowToNode()}
                NodeTags::createEmpty(),
                Timestamps::create(
                // TODO replace with $nodeRow['created'] and $nodeRow['originalcreated'] once projection has implemented support
                    QueryUtility::parseDateTimeString('2023-03-17 12:00:00'),
                    QueryUtility::parseDateTimeString('2023-03-17 12:00:00'),
                    null,
                    null,
                ),
                $this->visibilityConstraints,
            );
            $nodeAggregateId = $node->aggregateId->value;
            $level = (int)$nodeJson['depth'];
            $subtree = Subtree::create(
                $level,
                $node,
                array_key_exists($nodeAggregateId, $subtreesByParentNodeId) ?
                    Subtrees::fromArray($subtreesByParentNodeId[$nodeAggregateId]) :
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
        return Nodes::createEmpty();
    }

    public function countAncestorNodes(
        NodeAggregateId $entryNodeAggregateId,
        Filter\CountAncestorNodesFilter $filter
    ): int {
        return 0;
    }

    public function findClosestNode(
        NodeAggregateId $entryNodeAggregateId,
        FindClosestNodeFilter $filter
    ): ?Node {
        return null;
    }

    public function findDescendantNodes(
        NodeAggregateId $entryNodeAggregateId,
        FindDescendantNodesFilter $filter
    ): Nodes {
        return Nodes::createEmpty();
    }

    public function countDescendantNodes(NodeAggregateId $entryNodeAggregateId, Filter\CountDescendantNodesFilter $filter): int
    {
        // TODO: Implement countDescendantNodes() method.
        return 0;
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
