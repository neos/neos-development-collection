<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;

final readonly class ProjectionWriteQueries
{
    private ContentGraphTableNames $tableNames;

    public function __construct(ContentRepositoryId $contentRepositoryId)
    {
        $this->tableNames = ContentGraphTableNames::create($contentRepositoryId);
    }

    /**
     * @throws DBALException
     */
    public function insertNodeRecord(
        Connection $databaseConnection,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        SerializedPropertyValues $properties,
        NodeTypeName $nodeTypeName,
        NodeAggregateClassification $classification,
        ?NodeName $nodeName = null
    ): NodeRelationAnchorPoint
    {
        $result = $databaseConnection->executeQuery(
            <<<SQL
                insert into {$this->tableNames->node()} (
                                    nodeaggregateid,
                                    origindimensionspacepoint,
                                    origindimensionspacepointhash,
                                    nodetypename,
                                    properties,
                                    classification,
                                    nodename
                )
                values
                (:nodeaggregateid, :origindimensionspacepoint, :origindimensionspacepointhash, :nodetypename, :properties, :classification, :nodename)
                -- auto-increment
                returning relationanchorpoint
            SQL,
            [
                'nodeaggregateid' => $nodeAggregateId->value,
                'origindimensionspacepoint' => $originDimensionSpacePoint->toJson(),
                'origindimensionspacepointhash' => $originDimensionSpacePoint->hash,
                'nodetypename' => $nodeTypeName->value,
                'properties' => $properties->jsonSerialize(),
                'classification' => $classification->value,
                'nodename' => $nodeName?->value
            ]
        );

        $row = $result->fetchAssociative();
        if ($row === false) {
            // TODO handle error, is that even possible?
        }

        return NodeRelationAnchorPoint::fromInteger($row['relationanchorpoint']);
    }

    /**
     * @throws DBALException
     */
    public function updateNodeRecord(Connection $databaseConnection, NodeRecord $nodeRecord): void
    {
        $databaseConnection->update(
            $this->tableNames->node(),
            [
                'origindimensionspacepoint' => $nodeRecord->originDimensionSpacePoint->toJson(),
                'origindimensionspacepointhash' => $nodeRecord->originDimensionSpacePoint->hash,
                'nodeaggregateid' => $nodeRecord->nodeAggregateId->value,
                'nodetypename' => $nodeRecord->nodeTypeName->value,
                'classification' => $nodeRecord->classification->value,
                'properties' => json_encode($nodeRecord->properties),
                'nodename' => $nodeRecord->nodeName?->value ?? '',
            ],
            [
                'relationanchorpoint' => $nodeRecord->relationAnchorPoint
            ]
        );
    }

    /**
     * @throws DBALException
     */
    public function removeNodeRecord(Connection $databaseConnection, NodeRecord $nodeRecord): void
    {
        $databaseConnection->delete($this->tableNames->node(), [
            'relationanchorpoint' => $nodeRecord->relationAnchorPoint->value
        ]);
    }

    public function removeReferenceFromDatabaseForSource(
        Connection $databaseConnection,
        NodeRelationAnchorPoint $sourceNodeAnchor
    ): void {
        $databaseConnection->delete($this->tableNames->referenceRelation(), [
            'sourcenodeanchor' => $sourceNodeAnchor->value
        ]);
    }

    /**
     * @throws DBALException
     */
    public function addReferenceToDatabase(
        Connection $databaseConnection,
        ReferenceRelationRecord $relationRecord
    ): void
    {
        $databaseConnection->insert($this->tableNames->referenceRelation(), [
            'sourcenodeanchor' => $relationRecord->sourceNodeAnchor->value,
            'name' => $relationRecord->name->value,
            'position' => $relationRecord->position,
            'properties' => $relationRecord->properties
                ? \json_encode($relationRecord->properties)
                : null,
            'targetnodeaggregateid' => $relationRecord->targetNodeAggregateId->value
        ]);
    }

    public function replaceParentNodeAnchorOnHierarchyRecord(
        Connection $databaseConnection,
        array $hierarchyRelationId,
        NodeRelationAnchorPoint $newParentNodeAnchor
    ): void {
        /** @todo do this directly in the database */
        $databaseConnection->update(
            $this->tableNames->hierarchyRelation(),
            [
                'parentnodeanchor' => $newParentNodeAnchor->value
            ],
            $hierarchyRelationId
        );
    }

    public function replaceChildNodeAnchorOnHierarchyRecord(
        NodeRelationAnchorPoint $oldChildNodeAnchor,
        NodeRelationAnchorPoint $newChildNodeAnchor,
        Connection $databaseConnection,
        string $tableNamePrefix
    ): void {
        // TODO
        //$this->updateChildNodeAnchors($childNodeAnchors, $databaseConnection, $tableNamePrefix);
    }

    public function addChildNodeAnchorBeforeSuccessor(
        Connection $databaseConnection,
        array $hierarchyRelationId,
        NodeRelationAnchorPoint $newChildNodeAnchor,
        ?NodeRelationAnchorPoint $successor
    ): void {
        $databaseConnection->executeQuery(
            <<<SQL
                update {$this->tableNames->hierarchyRelation()}
                set childnodeanchors = insert_into_array_before_successor(childnodeanchors, :new_anchor, :successor::bigint)
                where contentstreamid = :contentstreamid
                  and parentnodeanchor = :parentnodeanchor
                  and dimensionspacepointhash = :dimensionspacepointhash
            SQL,
            [
                'contentstreamid' => $hierarchyRelationId['contentstreamid'],
                'parentnodeanchor' => $hierarchyRelationId['parentnodeanchor'],
                'dimensionspacepointhash' => $hierarchyRelationId['dimensionspacepointhash'],
                'new_anchor' => $newChildNodeAnchor->value,
                'successor' => $successor?->value
            ]
        );
    }

    public function removeChildNodeAnchorFromHierarchyRecord(
        Connection $databaseConnection,
        array $hierarchyRelationId,
        NodeRelationAnchorPoint $childNodeAnchor,
    ): void {
        $databaseConnection->executeQuery(
            <<<SQL
                with updated as (
                    update {$this->tableNames->hierarchyRelation()}
                    set childnodeanchors = array_remove(childnodeanchors, :childnodeanchor_to_remove)
                    where contentstreamid = :contentstreamid
                      and parentnodeanchor = :parentnodeanchor
                      and dimensionspacepointhash = :dimensionspacepointhash
                    returning contentstreamid, parentnodeanchor, dimensionspacepointhash, childnodeanchors
                )
                delete from {$this->tableNames->hierarchyRelation()} h
                using updated as u
                where h.contentstreamid = u.contentstreamid
                  and h.parentnodeanchor = u.parentnodeanchor
                  and h.dimensionspacepointhash = u.dimensionspacepointhash
                  -- we only delete the record, if there are no more child nodes
                  and array_length(u.childnodeanchors, 1) = 0
            SQL,
            [
                'contentstreamid' => $hierarchyRelationId['contentstreamid'],
                'parentnodeanchor' => $hierarchyRelationId['parentnodeanchor'],
                'dimensionspacepointhash' => $hierarchyRelationId['dimensionspacepointhash'],
                'childnodeanchor_to_remove' => $childNodeAnchor->value
            ]
        );
    }

    private function updateChildNodeAnchors(
        Connection $databaseConnection,
        array $hierarchyRelationId,
        NodeRelationAnchorPoints $newChildNodeAnchors
    ): void {
        $databaseConnection->update(
            $this->tableNames->hierarchyRelation(),
            [
                'childnodeanchors' => $newChildNodeAnchors->toDatabaseString()
            ],
            $hierarchyRelationId
        );
    }

    /**
     * @throws DBALException
     */
    public function addHierarchyRelationRecordToDatabase(
        Connection $databaseConnection,
        HierarchyRelationRecord $hierarchyRelationRecord
    ): void
    {
        $databaseConnection->insert(
            $this->tableNames->hierarchyRelation(),
            [
                'contentstreamid' => $hierarchyRelationRecord->contentStreamId->value,
                'parentnodeanchor' => $hierarchyRelationRecord->parentNodeAnchor->value,
                'dimensionspacepoint' => $hierarchyRelationRecord->dimensionSpacePoint->toJson(),
                'dimensionspacepointhash' => $hierarchyRelationRecord->dimensionSpacePoint->hash,
                'childnodeanchors' => $hierarchyRelationRecord->childNodeAnchorPoints->toDatabaseString()
            ]
        );
    }

    /**
     * @throws DBALException
     */
    public function removeHierarchyRelationFromDatabase(
        Connection $databaseConnection,
        array $hierarchyRelationId
    ): void
    {
        $databaseConnection->delete($this->tableNames->hierarchyRelation(), $hierarchyRelationId);
    }

}
