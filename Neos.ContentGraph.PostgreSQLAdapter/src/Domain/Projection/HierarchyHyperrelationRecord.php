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
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The active record for reading and writing hierarchy hyperrelations from and to the database
 *
 * @internal
 */
final class HierarchyHyperrelationRecord
{
    public ContentStreamId $contentStreamId;

    public DimensionSpacePoint $dimensionSpacePoint;

    public NodeRelationAnchorPoint $parentNodeAnchor;

    /**
     * The child node relation anchor points, indexed by sorting position
     */
    public NodeRelationAnchorPoints $childNodeAnchors;

    public function __construct(
        ContentStreamId $contentStreamId,
        NodeRelationAnchorPoint $parentNodeAnchor,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRelationAnchorPoints $childNodeAnchorPoints
    ) {
        $this->contentStreamId = $contentStreamId;
        $this->parentNodeAnchor = $parentNodeAnchor;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->childNodeAnchors = $childNodeAnchorPoints;
    }

    /**
     * @param array<string,string> $databaseRow
     */
    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            ContentStreamId::fromString($databaseRow['contentstreamid']),
            NodeRelationAnchorPoint::fromString($databaseRow['parentnodeanchor']),
            DimensionSpacePoint::fromJsonString($databaseRow['dimensionspacepoint']),
            NodeRelationAnchorPoints::fromDatabaseString(
                $databaseRow['childnodeanchors']
            )
        );
    }

    public function replaceParentNodeAnchor(
        NodeRelationAnchorPoint $newParentNodeAnchor,
        Connection $databaseConnection,
        string $tableNamePrefix
    ): void {
        /** @todo do this directly in the database */
        $databaseConnection->update(
            $tableNamePrefix . '_hierarchyhyperrelation',
            [
                'parentnodeanchor' => (string)$newParentNodeAnchor
            ],
            $this->getDatabaseIdentifier()
        );
        $this->parentNodeAnchor = $newParentNodeAnchor;
    }

    public function replaceChildNodeAnchor(
        NodeRelationAnchorPoint $oldChildNodeAnchor,
        NodeRelationAnchorPoint $newChildNodeAnchor,
        Connection $databaseConnection,
        string $tableNamePrefix
    ): void {
        /** @todo do this directly in the database */
        $childNodeAnchors = $this->childNodeAnchors->replace(
            $oldChildNodeAnchor,
            $newChildNodeAnchor
        );
        $this->updateChildNodeAnchors($childNodeAnchors, $databaseConnection, $tableNamePrefix);
    }

    public function addChildNodeAnchor(
        NodeRelationAnchorPoint $childNodeAnchor,
        ?NodeRelationAnchorPoint $succeedingSiblingAnchor,
        Connection $databaseConnection,
        string $tableNamePrefix
    ): void {
        /** @todo do this directly in the database */
        $childNodeAnchors = $this->childNodeAnchors->add(
            $childNodeAnchor,
            $succeedingSiblingAnchor
        );
        $this->updateChildNodeAnchors($childNodeAnchors, $databaseConnection, $tableNamePrefix);
    }

    public function removeChildNodeAnchor(
        NodeRelationAnchorPoint $childNodeAnchor,
        Connection $databaseConnection,
        string $tableNamePrefix
    ): void {
        /** @todo do this directly in the database */
        $childNodeAnchors = $this->childNodeAnchors->remove($childNodeAnchor);
        if (count($childNodeAnchors) === 0) {
            $this->removeFromDatabase($databaseConnection, $tableNamePrefix);
        } else {
            $this->updateChildNodeAnchors($childNodeAnchors, $databaseConnection, $tableNamePrefix);
        }
    }

    private function updateChildNodeAnchors(
        NodeRelationAnchorPoints $childNodeAnchors,
        Connection $databaseConnection,
        string $tableNamePrefix
    ): void {
        $databaseConnection->update(
            $tableNamePrefix . '_hierarchyhyperrelation',
            [
                'childnodeanchors' => $childNodeAnchors->toDatabaseString()
            ],
            $this->getDatabaseIdentifier()
        );
        $this->childNodeAnchors = $childNodeAnchors;
    }

    /**
     * @throws DBALException
     */
    public function addToDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->insert(
            $tableNamePrefix . '_hierarchyhyperrelation',
            [
                'contentstreamid' => $this->contentStreamId,
                'parentnodeanchor' => $this->parentNodeAnchor,
                'dimensionspacepoint' => \json_encode($this->dimensionSpacePoint),
                'dimensionspacepointhash' => $this->dimensionSpacePoint->hash,
                'childnodeanchors' => $this->childNodeAnchors->toDatabaseString()
            ]
        );
    }

    /**
     * @throws DBALException
     */
    public function removeFromDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->delete($tableNamePrefix . '_hierarchyhyperrelation', $this->getDatabaseIdentifier());
    }

    /**
     * @return array<string,string>
     */
    public function getDatabaseIdentifier(): array
    {
        return [
            'contentstreamid' => (string)$this->contentStreamId,
            'parentnodeanchor' => (string)$this->parentNodeAnchor,
            'dimensionspacepointhash' => $this->dimensionSpacePoint->hash
        ];
    }
}
