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
     * @return array<string,string>
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
                    WITH updated AS (
                        UPDATE {$tableName}
                        SET childnodeanchors = array_remove(childnodeanchors, :childnodeanchor_to_remove),
                            subtreetags = subtreetags - :childnodeanchor_to_remove_text
                        WHERE contentstreamid = :contentstreamid
                          AND parentnodeanchor = :parentnodeanchor
                          AND dimensionspacepointhash = :dimensionspacepointhash
                        RETURNING contentstreamid, parentnodeanchor, dimensionspacepointhash, childnodeanchors
                    )
                    DELETE FROM {$tableName} h
                    USING updated AS u
                    WHERE h.contentstreamid = u.contentstreamid
                      AND h.parentnodeanchor = u.parentnodeanchor
                      AND h.dimensionspacepointhash = u.dimensionspacepointhash
                      AND array_length(u.childnodeanchors, 1) IS NULL
                SQL,
                [
                    'contentstreamid' => $id['contentstreamid'],
                    'parentnodeanchor' => $id['parentnodeanchor'],
                    'dimensionspacepointhash' => $id['dimensionspacepointhash'],
                    'childnodeanchor_to_remove' => $childNodeAnchor->value,
                    'childnodeanchor_to_remove_text' => (string)$childNodeAnchor->value,
                ]
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to remove child node anchor from hierarchy relation: %s', $e->getMessage()), 1716484900, $e);
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
