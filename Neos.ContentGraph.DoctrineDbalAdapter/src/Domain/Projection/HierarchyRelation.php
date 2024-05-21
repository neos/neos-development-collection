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
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\DimensionSpacePointsRepository;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The active record for reading and writing hierarchy relations from and to the database
 *
 * @internal
 */
final readonly class HierarchyRelation
{
    public function __construct(
        public NodeRelationAnchorPoint $parentNodeAnchor,
        public NodeRelationAnchorPoint $childNodeAnchor,
        public ContentStreamId $contentStreamId,
        public DimensionSpacePoint $dimensionSpacePoint,
        public string $dimensionSpacePointHash,
        public int $position,
        public NodeTags $subtreeTags,
    ) {
    }

    /**
     * @param Connection $databaseConnection
     */
    public function addToDatabase(Connection $databaseConnection, ContentGraphTableNames $tableNames): void
    {
        $dimensionSpacePoints = new DimensionSpacePointsRepository($databaseConnection, $tableNames);
        $dimensionSpacePoints->insertDimensionSpacePoint($this->dimensionSpacePoint);

        $databaseConnection->insert($tableNames->hierarchyRelation(), [
            'parentnodeanchor' => $this->parentNodeAnchor->value,
            'childnodeanchor' => $this->childNodeAnchor->value,
            'contentstreamid' => $this->contentStreamId->value,
            'dimensionspacepointhash' => $this->dimensionSpacePointHash,
            'position' => $this->position,
            'subtreetags' => json_encode($this->subtreeTags, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT),
        ]);
    }

    /**
     * @param Connection $databaseConnection
     */
    public function removeFromDatabase(Connection $databaseConnection, ContentGraphTableNames $tableNames): void
    {
        $databaseConnection->delete($tableNames->hierarchyRelation(), $this->getDatabaseId());
    }

    /**
     * @param NodeRelationAnchorPoint $childAnchorPoint
     * @param Connection $databaseConnection
     */
    public function assignNewChildNode(
        NodeRelationAnchorPoint $childAnchorPoint,
        Connection $databaseConnection,
        ContentGraphTableNames $tableNames
    ): void {
        $databaseConnection->update(
            $tableNames->hierarchyRelation(),
            [
                'childnodeanchor' => $childAnchorPoint->value
            ],
            $this->getDatabaseId()
        );
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function assignNewParentNode(
        NodeRelationAnchorPoint $parentAnchorPoint,
        ?int $position,
        Connection $databaseConnection,
        ContentGraphTableNames $tableNames
    ): void {
        $data = [
            'parentnodeanchor' => $parentAnchorPoint->value
        ];
        if (!is_null($position)) {
            $data['position'] = $position;
        }
        $databaseConnection->update(
            $tableNames->hierarchyRelation(),
            $data,
            $this->getDatabaseId()
        );
    }

    public function assignNewPosition(int $position, Connection $databaseConnection, ContentGraphTableNames $tableNames): void
    {
        $databaseConnection->update(
            $tableNames->hierarchyRelation(),
            [
                'position' => $position
            ],
            $this->getDatabaseId()
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function getDatabaseId(): array
    {
        return [
            'parentnodeanchor' => $this->parentNodeAnchor->value,
            'childnodeanchor' => $this->childNodeAnchor->value,
            'contentstreamid' => $this->contentStreamId->value,
            'dimensionspacepointhash' => $this->dimensionSpacePointHash
        ];
    }
}
