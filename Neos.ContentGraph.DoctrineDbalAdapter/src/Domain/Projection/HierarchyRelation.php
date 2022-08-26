<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\NodeName;

/**
 * The active record for reading and writing hierarchy relations from and to the database
 *
 * @internal
 */
final class HierarchyRelation
{
    public function __construct(
        public NodeRelationAnchorPoint $parentNodeAnchor,
        public NodeRelationAnchorPoint $childNodeAnchor,
        public ?NodeName $name,
        public ContentStreamIdentifier $contentStreamIdentifier,
        public DimensionSpacePoint $dimensionSpacePoint,
        public string $dimensionSpacePointHash,
        public int $position
    ) {
    }

    /**
     * @param Connection $databaseConnection
     */
    public function addToDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->insert($tableNamePrefix . '_hierarchyrelation', [
            'parentnodeanchor' => $this->parentNodeAnchor,
            'childnodeanchor' => $this->childNodeAnchor,
            'name' => $this->name,
            'contentstreamidentifier' => $this->contentStreamIdentifier,
            'dimensionspacepoint' => json_encode($this->dimensionSpacePoint),
            'dimensionspacepointhash' => $this->dimensionSpacePointHash,
            'position' => $this->position
        ]);
    }

    /**
     * @param Connection $databaseConnection
     */
    public function removeFromDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->delete($tableNamePrefix . '_hierarchyrelation', $this->getDatabaseIdentifier());
    }

    /**
     * @param NodeRelationAnchorPoint $childAnchorPoint
     * @param Connection $databaseConnection
     */
    public function assignNewChildNode(
        NodeRelationAnchorPoint $childAnchorPoint,
        Connection $databaseConnection,
        string $tableNamePrefix
    ): void {
        $databaseConnection->update(
            $tableNamePrefix . '_hierarchyrelation',
            [
                'childnodeanchor' => $childAnchorPoint
            ],
            $this->getDatabaseIdentifier()
        );
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function assignNewParentNode(
        NodeRelationAnchorPoint $parentAnchorPoint,
        ?int $position,
        Connection $databaseConnection,
        string $tableNamePrefix
    ): void {
        $data = [
            'parentnodeanchor' => $parentAnchorPoint
        ];
        if (!is_null($position)) {
            $data['position'] = $position;
        }
        $databaseConnection->update(
            $tableNamePrefix . '_hierarchyrelation',
            $data,
            $this->getDatabaseIdentifier()
        );
    }

    public function assignNewPosition(int $position, Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->update(
            $tableNamePrefix . '_hierarchyrelation',
            [
                'position' => $position
            ],
            $this->getDatabaseIdentifier()
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function getDatabaseIdentifier(): array
    {
        return [
            'parentnodeanchor' => $this->parentNodeAnchor,
            'childnodeanchor' => $this->childNodeAnchor,
            'contentstreamidentifier' => $this->contentStreamIdentifier,
            'dimensionspacepointhash' => $this->dimensionSpacePointHash
        ];
    }
}
