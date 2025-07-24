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

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyRelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoints;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionReadQueries;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The node disabling feature set for the hypergraph projector
 *
 * @internal
 */
trait NodeVariation
{

    abstract protected function getDatabaseConnection(): Connection;

    /**
     * @throws \Throwable
     */
    private function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event): void
    {
        $parameters = [
            'contentstreamid' => $event->contentStreamId->value,
            'nodeaggregateid' => $event->nodeAggregateId->value,
            'origindimensionspacepointhash' => $event->sourceOrigin->hash,
            'specializationorigin' => $event->specializationOrigin->toJson(),
            'specializationoriginhash' => $event->specializationOrigin->hash,
            'specializeddimensions' => json_encode($event->specializationSiblings
                ->toDimensionSpacePointSet()->getPointHashes())
        ];

        $query = <<<SQL
                    with
                        specialized_dimensions as (select *
                                                          from jsonb_array_elements_text(:specializeddimensions) sdim(specializeddimensionhash)),
                        -- we need the source node, to copy its values
                        source_node as (select *
                                         from neoscr_default_find_node_by_origin(
                                           :nodeaggregateid,
                                           :contentstreamid,
                                           :origindimensionspacepointhash
                                              )
                        ),
                        -- create the specialized copy and keep the auto-incremented ID
                        specialized_node_copy as (
                            insert into cr_default_p_graph_node
                              (nodeaggregateid, origindimensionspacepoint, origindimensionspacepointhash,
                               nodetypename, properties, classification, nodename)
                            select sn.nodeaggregateid,
                                   :specializationorigin,
                                   :specializationoriginhash,
                                   sn.nodetypename,
                                   sn.properties,
                                   sn.classification,
                                   sn.nodename
                            from source_node sn
                            returning relationanchorpoint
                        ),
                        -- we need the old covering node to decide how to connect the hierarchy
                        old_covering_node as (select relationanchorpoint
                                              from neoscr_default_find_node_by_coverage(
                                                :nodeaggregateid,
                                                :contentstreamid,
                                                :specializationoriginhash
                                                   )),
                        -- ### CASE 1 - an old covering node exists - replace hierarchy
                        -- Replace the old covering node with the specialized variant in
                        -- all hierarchy records (child and parent references).
                        update_ingoing_hierarchy as (
                          update cr_default_p_graph_hierarchyrelation
                            set childnodeanchors = array_replace(
                              cr_default_p_graph_hierarchyrelation.childnodeanchors,
                              o.relationanchorpoint,
                              s.relationanchorpoint
                                                   )
                            from old_covering_node o, specialized_node_copy s
                            where o.relationanchorpoint = any (childnodeanchors)
                              -- only affected dimensions
                              and dimensionspacepointhash = any (select d.specializeddimensionhash from specialized_dimensions d)
                              -- only if there is an old covering node
                              and o.relationanchorpoint is not null
                        ),
                        update_outgoing_hierarchy as (
                          update cr_default_p_graph_hierarchyrelation
                            set parentnodeanchor = s.relationanchorpoint
                            from old_covering_node o, specialized_node_copy s
                            where parentnodeanchor = o.relationanchorpoint
                              -- only affected dimensions
                              and dimensionspacepointhash = any (select d.specializeddimensionhash from specialized_dimensions d)
                              -- only if there is an old covering node
                              and o.relationanchorpoint is not null)
                    -- ### CASE 2 - an old covering node does not exist - create hierarchy
                    -- Add the specialized node as child to each relation entry of all dimensions
                    -- of the parent node aggregate.
                    update cr_default_p_graph_hierarchyrelation
                    set childnodeanchors = childnodeanchors || (select s.relationanchorpoint from specialized_node_copy s)
                    from (select sd.specializeddimensionhash,
                                 parent_hierarchy.parenthierarchyrelationanchor
                          from specialized_dimensions sd
                                 -- source parent node aggregate ID
                                 left join lateral (
                            select neoscr_default_get_parent_relationanchorpoint(
                                     :nodeaggregateid,
                                     :contentstreamid,
                                     sd.specializeddimensionhash
                                   ) as parenthierarchyrelationanchor
                            ) parent_hierarchy on true) parent_hierarchy_records
                    -- only if there is NO old covering node
                    where contentstreamid = :contentstreamid
                      and dimensionspacepointhash = parent_hierarchy_records.specializeddimensionhash
                      and parentnodeanchor = parent_hierarchy_records.parenthierarchyrelationanchor
                      and not exists(select 1 from old_covering_node)
        SQL;

        $this->getDatabaseConnection()->executeQuery($query, $parameters);

        /**
         * TODO references
        $this->copyReferenceRelations(
            $sourceNode->relationAnchorPoint,
            $specializedNode->relationAnchorPoint
        );
         */
    }

    private function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event): void
    {
        $sourceNode = $this->getReadQueries()->findNodeRecordByOrigin(
            $event->contentStreamId,
            $event->sourceOrigin,
            $event->nodeAggregateId
        );
        if (!$sourceNode) {
            throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
        }
        $generalizedNode = $this->copyNodeToOriginDimensionSpacePoint(
            $sourceNode,
            $event->generalizationOrigin
        );

        $this->replaceNodeRelationAnchorPoint(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->variantSucceedingSiblings->toDimensionSpacePointSet(),
            $generalizedNode->relationAnchorPoint
        );
        $this->addMissingHierarchyRelations(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->sourceOrigin,
            $generalizedNode->relationAnchorPoint,
            $event->variantSucceedingSiblings->toDimensionSpacePointSet(),
            get_class($event)
        );
        $this->copyReferenceRelations(
            $sourceNode->relationAnchorPoint,
            $generalizedNode->relationAnchorPoint
        );
    }

    private function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event): void
    {
        $sourceNode = $this->getReadQueries()->findNodeRecordByOrigin(
            $event->contentStreamId,
            $event->sourceOrigin,
            $event->nodeAggregateId
        );
        if (!$sourceNode) {
            throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
        }
        $peerNode = $this->copyNodeToOriginDimensionSpacePoint(
            $sourceNode,
            $event->peerOrigin
        );

        $this->replaceNodeRelationAnchorPoint(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->peerSucceedingSiblings->toDimensionSpacePointSet(),
            $peerNode->relationAnchorPoint
        );
        $this->addMissingHierarchyRelations(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->sourceOrigin,
            $peerNode->relationAnchorPoint,
            $event->peerSucceedingSiblings->toDimensionSpacePointSet(),
            get_class($event)
        );
        $this->copyReferenceRelations(
            $sourceNode->relationAnchorPoint,
            $peerNode->relationAnchorPoint
        );
    }

    /**
     * @throws DBALException
     */
    protected function copyNodeToOriginDimensionSpacePoint(
        NodeRecord $sourceNode,
        OriginDimensionSpacePoint $targetOrigin
    ): NodeRecord {
        $copy = new NodeRecord(
            NodeRelationAnchorPoint::create(),
            $sourceNode->nodeAggregateId,
            $targetOrigin,
            $targetOrigin->hash,
            $sourceNode->properties,
            $sourceNode->nodeTypeName,
            $sourceNode->classification,
            $sourceNode->nodeName
        );
        $copy->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);

        return $copy;
    }

    /**
     * @throws DBALException
     */
    protected function replaceNodeRelationAnchorPoint(
        ContentStreamId $contentStreamId,
        NodeAggregateId $affectedNodeAggregateId,
        DimensionSpacePointSet $affectedDimensionSpacePointSet,
        NodeRelationAnchorPoint $newNodeRelationAnchorPoint
    ): void {
        $currentNodeAnchorPointStatement = '
            WITH currentNodeAnchorPoint AS (
                SELECT relationanchorpoint FROM ' . $this->tableNamePrefix . '_node n
                    JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation p
                    ON n.relationanchorpoint = ANY(p.childnodeanchors)
                WHERE p.contentstreamid = :contentStreamId
                AND p.dimensionspacepointhash = :affectedDimensionSpacePointHash
                AND n.nodeaggregateid = :affectedNodeAggregateId
            )';
        $parameters = [
            'contentStreamId' => $contentStreamId->value,
            'newNodeRelationAnchorPoint' => $newNodeRelationAnchorPoint->value,
            'affectedNodeAggregateId' => $affectedNodeAggregateId->value
        ];
        foreach ($affectedDimensionSpacePointSet as $affectedDimensionSpacePoint) {
            $parentStatement = /** @lang PostgreSQL */
                $currentNodeAnchorPointStatement . '
                UPDATE ' . $this->tableNamePrefix . '_hierarchyhyperrelation
                    SET parentnodeanchor = :newNodeRelationAnchorPoint
                    WHERE contentstreamid = :contentStreamId
                        AND dimensionspacepointhash = :affectedDimensionSpacePointHash
                        AND parentnodeanchor = (SELECT relationanchorpoint FROM currentNodeAnchorPoint)
                ';
            $childStatement = /** @lang PostgreSQL */
                $currentNodeAnchorPointStatement . '
                UPDATE ' . $this->tableNamePrefix . '_hierarchyhyperrelation
                    SET childnodeanchors = array_replace(
                        childnodeanchors,
                        (SELECT relationanchorpoint FROM currentNodeAnchorPoint),
                        :newNodeRelationAnchorPoint
                    )
                    WHERE contentstreamid = :contentStreamId
                        AND dimensionspacepointhash = :affectedDimensionSpacePointHash
                        AND (SELECT relationanchorpoint FROM currentNodeAnchorPoint) = ANY(childnodeanchors)
                ';
            $parameters['affectedDimensionSpacePointHash'] = $affectedDimensionSpacePoint->hash;
            $this->getDatabaseConnection()->executeStatement($parentStatement, $parameters);
            $this->getDatabaseConnection()->executeStatement($childStatement, $parameters);
        }
    }

    protected function addMissingHierarchyRelations(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $sourceOrigin,
        NodeRelationAnchorPoint $targetRelationAnchor,
        DimensionSpacePointSet $coverage,
        string $eventClassName
    ): void {
        $missingCoverage = $coverage->getDifference(
            $this->getReadQueries()->findCoverageByNodeAggregateId(
                $contentStreamId,
                $nodeAggregateId
            )
        );
        if ($missingCoverage->count() > 0) {
            $sourceParentNode = $this->getReadQueries()->findParentNodeRecordByOrigin(
                $contentStreamId,
                $sourceOrigin,
                $nodeAggregateId
            );
            if (!$sourceParentNode) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceParentNodeIsMissing($eventClassName);
            }
            $parentNodeAggregateId = $sourceParentNode->nodeAggregateId;
            $sourceSucceedingSiblingNode = $this->getReadQueries()->findParentNodeRecordByOrigin(
                $contentStreamId,
                $sourceOrigin,
                $nodeAggregateId
            );
            foreach ($missingCoverage as $uncoveredDimensionSpacePoint) {
                // The parent node aggregate might be varied as well,
                // so we need to find a parent node for each covered dimension space point

                // First we check for an already existing hyperrelation
                $hierarchyRelation = $this->getReadQueries()->findChildHierarchyHyperrelationRecord(
                    $contentStreamId,
                    $uncoveredDimensionSpacePoint,
                    $parentNodeAggregateId
                );

                if ($hierarchyRelation && $sourceSucceedingSiblingNode) {
                    // If it exists, we need to look for a succeeding sibling to keep some order of nodes
                    $targetSucceedingSibling = $this->getReadQueries()->findNodeRecordByCoverage(
                        $contentStreamId,
                        $uncoveredDimensionSpacePoint,
                        $sourceSucceedingSiblingNode->nodeAggregateId
                    );

                    $hierarchyRelation->addChildNodeAnchor(
                        $targetRelationAnchor,
                        $targetSucceedingSibling?->relationAnchorPoint,
                        $this->getDatabaseConnection(),
                        $this->tableNamePrefix
                    );
                } else {
                    $targetParentNode = $this->getReadQueries()->findNodeRecordByCoverage(
                        $contentStreamId,
                        $uncoveredDimensionSpacePoint,
                        $parentNodeAggregateId
                    );
                    if (!$targetParentNode) {
                        throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(
                            $eventClassName
                        );
                    }
                    $hierarchyRelation = new HierarchyRelationRecord(
                        $contentStreamId,
                        $targetParentNode->relationAnchorPoint,
                        $uncoveredDimensionSpacePoint,
                        NodeRelationAnchorPoints::fromArray([$targetRelationAnchor])
                    );
                    $hierarchyRelation->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
                }
            }
        }
    }

    /**
     * @throws DBALException
     */
    protected function assignNewChildNodeToAffectedHierarchyRelations(
        ContentStreamId $contentStreamId,
        NodeRelationAnchorPoint $oldChildAnchor,
        NodeRelationAnchorPoint $newChildAnchor,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): void {
        foreach (
            $this->getReadQueries()->findIngoingHierarchyHyperrelationRecords(
                $contentStreamId,
                $oldChildAnchor,
                $affectedDimensionSpacePoints
            ) as $ingoingHierarchyHyperrelationRecord
        ) {
            $ingoingHierarchyHyperrelationRecord->replaceChildNodeAnchor(
                $oldChildAnchor,
                $newChildAnchor,
                $this->getDatabaseConnection(),
                $this->tableNamePrefix
            );
        }
    }

    /**
     * @throws DBALException
     */
    protected function assignNewParentNodeToAffectedHierarchyRelations(
        ContentStreamId $contentStreamId,
        NodeRelationAnchorPoint $oldParentAnchor,
        NodeRelationAnchorPoint $newParentAnchor,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): void {
        foreach (
            $this->getReadQueries()->findOutgoingHierarchyHyperrelationRecords(
                $contentStreamId,
                $oldParentAnchor,
                $affectedDimensionSpacePoints
            ) as $outgoingHierarchyHyperrelationRecord
        ) {
            $outgoingHierarchyHyperrelationRecord->replaceParentNodeAnchor(
                $newParentAnchor,
                $this->getDatabaseConnection(),
                $this->tableNamePrefix
            );
        }
    }

    protected function copyReferenceRelations(
        NodeRelationAnchorPoint $sourceRelationAnchorPoint,
        NodeRelationAnchorPoint $newSourceRelationAnchorPoint
    ): void {
        // we don't care whether the target node aggregate covers the variant's origin
        // since if it doesn't, it already didn't match the source's coverage before

        $this->getDatabaseConnection()->executeStatement('
                INSERT INTO ' . $this->tableNamePrefix . '_referencerelation (
                  sourcenodeanchor,
                  name,
                  position,
                  properties,
                  targetnodeaggregateid
                )
                SELECT
                  :newSourceRelationAnchorPoint AS sourcenodeanchor,
                  ref.name,
                  ref.position,
                  ref.properties,
                  ref.targetnodeaggregateid
                FROM
                    ' . $this->tableNamePrefix . '_referencerelation ref
                    WHERE ref.sourcenodeanchor = :sourceNodeAnchorPoint
            ', [
            'sourceNodeAnchorPoint' => $sourceRelationAnchorPoint->value,
            'newSourceRelationAnchorPoint' => $newSourceRelationAnchorPoint->value
        ]);
    }
}
