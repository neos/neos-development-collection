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
use Neos\ContentRepository\Core\Projection\ContentGraph\SubtreeTagsWithInherited;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;

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
        public ?NodeName $name,
        public ContentStreamId $contentStreamId,
        public DimensionSpacePoint $dimensionSpacePoint,
        public string $dimensionSpacePointHash,
        public int $position,
        public SubtreeTagsWithInherited $subtreeTags,
    ) {
    }

    /**
     * @param Connection $databaseConnection
     */
    public function addToDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->insert($tableNamePrefix . '_hierarchyrelation', [
            'parentnodeanchor' => $this->parentNodeAnchor->value,
            'childnodeanchor' => $this->childNodeAnchor->value,
            'name' => $this->name?->value,
            'contentstreamid' => $this->contentStreamId->value,
            'dimensionspacepoint' => $this->dimensionSpacePoint->toJson(),
            'dimensionspacepointhash' => $this->dimensionSpacePointHash,
            'position' => $this->position,
            'subtreetags' => json_encode($this->subtreeTags, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT),
        ]);
    }

    /**
     * @param Connection $databaseConnection
     */
    public function removeFromDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->delete($tableNamePrefix . '_hierarchyrelation', $this->getDatabaseId());
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
        string $tableNamePrefix
    ): void {
        $data = [
            'parentnodeanchor' => $parentAnchorPoint->value
        ];
        if (!is_null($position)) {
            $data['position'] = $position;
        }
        $databaseConnection->update(
            $tableNamePrefix . '_hierarchyrelation',
            $data,
            $this->getDatabaseId()
        );
    }

    public function assignNewPosition(int $position, Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->update(
            $tableNamePrefix . '_hierarchyrelation',
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
