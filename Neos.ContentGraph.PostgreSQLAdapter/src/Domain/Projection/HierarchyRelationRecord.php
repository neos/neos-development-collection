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

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The active record for reading hierarchy from and to the database.
 * The child node relation anchor points are indexed by sorting position.
 *
 * @internal
 */
final readonly class HierarchyRelationRecord
{
    public function __construct(
        public ContentStreamId $contentStreamId,
        public NodeRelationAnchorPoint $parentNodeAnchor,
        public DimensionSpacePoint $dimensionSpacePoint,
        public NodeRelationAnchorPoints $childNodeAnchors
    ) {
    }

    /**
     * @param array<string,mixed> $databaseRow
     */
    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            ContentStreamId::fromString($databaseRow['contentstreamid']),
            NodeRelationAnchorPoint::fromInteger($databaseRow['parentnodeanchor']),
            DimensionSpacePoint::fromJsonString($databaseRow['dimensionspacepoint']),
            NodeRelationAnchorPoints::fromDatabaseString(
                $databaseRow['childnodeanchors']
            )
        );
    }

    /**
     * @return array<string,string|int>
     */
    public function getDatabaseIdentifier(): array
    {
        return [
            'contentstreamid' => $this->contentStreamId->value,
            'parentnodeanchor' => $this->parentNodeAnchor->value,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->hash
        ];
    }

    public function removeChildNodeAnchor(
        NodeRelationAnchorPoint $childNodeAnchor,
        Connection $databaseConnection,
        ContentGraphTableNames $tableNames
    ): void {
        $tableName = $tableNames->hierarchyRelation();
        $id = $this->getDatabaseIdentifier();
        try {
            $databaseConnection->executeStatement(
                <<<SQL
                    UPDATE {$tableName}
                    SET childnodeanchors = array_remove(childnodeanchors, :childnodeanchor_to_remove),
                        subtreetags = subtreetags - :childnodeanchor_to_remove_text
                    WHERE contentstreamid = :contentstreamid
                      AND parentnodeanchor = :parentnodeanchor
                      AND dimensionspacepointhash = :dimensionspacepointhash
                SQL,
                [
                    'contentstreamid' => $id['contentstreamid'],
                    'parentnodeanchor' => $id['parentnodeanchor'],
                    'dimensionspacepointhash' => $id['dimensionspacepointhash'],
                    'childnodeanchor_to_remove' => $childNodeAnchor->value,
                    'childnodeanchor_to_remove_text' => (string)$childNodeAnchor->value,
                ]
            );

            $databaseConnection->executeStatement(
                <<<SQL
                    DELETE FROM {$tableName}
                    WHERE contentstreamid = :contentstreamid
                      AND parentnodeanchor = :parentnodeanchor
                      AND dimensionspacepointhash = :dimensionspacepointhash
                      AND childnodeanchors = '{}'
                SQL,
                [
                    'contentstreamid' => $id['contentstreamid'],
                    'parentnodeanchor' => $id['parentnodeanchor'],
                    'dimensionspacepointhash' => $id['dimensionspacepointhash'],
                ]
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to remove child node anchor from hierarchy relation: %s', $e->getMessage()), 1716484900, $e);
        }
    }

    public function replaceChildNodeAnchor(
        NodeRelationAnchorPoint $oldChildNodeAnchor,
        NodeRelationAnchorPoint $newChildNodeAnchor,
        Connection $databaseConnection,
        ContentGraphTableNames $tableNames
    ): void {
        $tableName = $tableNames->hierarchyRelation();
        $id = $this->getDatabaseIdentifier();
        try {
            $databaseConnection->executeStatement(
                <<<SQL
                    UPDATE {$tableName}
                    SET childnodeanchors = array_replace(childnodeanchors, :old_anchor::bigint, :new_anchor::bigint),
                        subtreetags = CASE
                            WHEN jsonb_exists(subtreetags, :old_anchor_text)
                            THEN (subtreetags - :old_anchor_text)
                                 || jsonb_build_object(:new_anchor_text::text, subtreetags->(:old_anchor_text::text))
                            ELSE subtreetags
                        END
                    WHERE contentstreamid = :contentstreamid
                      AND parentnodeanchor = :parentnodeanchor
                      AND dimensionspacepointhash = :dimensionspacepointhash
                SQL,
                [
                    'contentstreamid' => $id['contentstreamid'],
                    'parentnodeanchor' => $id['parentnodeanchor'],
                    'dimensionspacepointhash' => $id['dimensionspacepointhash'],
                    'old_anchor' => $oldChildNodeAnchor->value,
                    'new_anchor' => $newChildNodeAnchor->value,
                    'old_anchor_text' => (string)$oldChildNodeAnchor->value,
                    'new_anchor_text' => (string)$newChildNodeAnchor->value,
                ]
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to replace child node anchor in hierarchy relation: %s', $e->getMessage()), 1716484950, $e);
        }
    }

    public function replaceParentNodeAnchor(
        NodeRelationAnchorPoint $newParentNodeAnchor,
        Connection $databaseConnection,
        ContentGraphTableNames $tableNames
    ): void {
        try {
            $databaseConnection->update(
                $tableNames->hierarchyRelation(),
                [
                    'parentnodeanchor' => $newParentNodeAnchor->value,
                ],
                $this->getDatabaseIdentifier()
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to replace parent node anchor in hierarchy relation: %s', $e->getMessage()), 1716484960, $e);
        }
    }

    public function removeFromDatabase(Connection $databaseConnection, ContentGraphTableNames $tableNames): void
    {
        try {
            $databaseConnection->delete($tableNames->hierarchyRelation(), $this->getDatabaseIdentifier());
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to remove hierarchy relation from database: %s', $e->getMessage()), 1716484910, $e);
        }
    }
}
