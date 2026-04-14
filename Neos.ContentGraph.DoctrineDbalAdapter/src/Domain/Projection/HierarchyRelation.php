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
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\DimensionSpacePointsRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;

/**
 * The active record for reading and writing hierarchy relations from and to the database
 *
 * @internal
 */
final readonly class HierarchyRelation
{
    public function __construct(
        public HierarchyRelationId $hierarchyRelationId,
        public ContentStreamLayer $contentStreamLayer,
        public NodeRelationAnchorPoint $parentNodeAnchor,
        public NodeRelationAnchorPoint $childNodeAnchor,
        public DimensionSpacePoint $dimensionSpacePoint,
        public string $dimensionSpacePointHash,
        public int $position,
        public NodeTags $subtreeTags,
    ) {
    }

    public function with(
        ?HierarchyRelationId $hierarchyRelationDId = null,
        ?NodeRelationAnchorPoint $parentNodeAnchor = null,
        ?NodeRelationAnchorPoint $childNodeAnchor = null,
        ?ContentStreamLayer $contentStreamLayer = null,
        ?DimensionSpacePoint $dimensionSpacePoint = null,
        ?string $dimensionSpacePointHash = null,
        ?int $position = null,
        ?NodeTags $subtreeTags = null,
    ): self {
        return new self(
            hierarchyRelationId: $hierarchyRelationDId ?? $this->hierarchyRelationId,
            contentStreamLayer: $contentStreamLayer ?? $this->contentStreamLayer,
            parentNodeAnchor: $parentNodeAnchor ?? $this->parentNodeAnchor,
            childNodeAnchor: $childNodeAnchor ?? $this->childNodeAnchor,
            dimensionSpacePoint: $dimensionSpacePoint ?? $this->dimensionSpacePoint,
            dimensionSpacePointHash: $dimensionSpacePointHash ?? $this->dimensionSpacePointHash,
            position: $position ?? $this->position,
            subtreeTags: $subtreeTags ?? $this->subtreeTags,
        );
    }

    public function addToDatabase(Connection $databaseConnection, ContentGraphTableNames $tableNames): void
    {
        $dimensionSpacePoints = new DimensionSpacePointsRepository($databaseConnection, $tableNames);
        $dimensionSpacePoints->insertDimensionSpacePoint($this->dimensionSpacePoint);
        try {
            $subtreeTagsJson = json_encode($this->subtreeTags, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT);
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to JSON-encode Subtree Tags: %s', $e->getMessage()), 1716484752, $e);
        }

        try {
            $databaseConnection->insert($tableNames->hierarchyRelation(), [
                'id' => $this->hierarchyRelationId->value,
                'parentnodeanchor' => $this->parentNodeAnchor->value,
                'childnodeanchor' => $this->childNodeAnchor->value,
                'contentstreamlayer' => $this->contentStreamLayer->value,
                'dimensionspacepointhash' => $this->dimensionSpacePointHash,
                'position' => $this->position,
                'subtreetags' => $subtreeTagsJson,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to add hierarchy relation to database: %s', $e->getMessage()), 1716484789, $e);
        }
    }

    public function removeFromDatabase(Connection $databaseConnection, ContentGraphTableNames $tableNames): void
    {
        try {
            $databaseConnection->delete($tableNames->hierarchyRelation(), $this->getDatabaseId());
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to remove hierarchy relation from database: %s', $e->getMessage()), 1716484823, $e);
        }
    }

    public function assignNewChildNode(
        NodeRelationAnchorPoint $childAnchorPoint,
        Connection $databaseConnection,
        ContentGraphTableNames $tableNames
    ): void {
        try {
            $databaseConnection->update(
                $tableNames->hierarchyRelation(),
                [
                    'childnodeanchor' => $childAnchorPoint->value
                ],
                $this->getDatabaseId()
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to update hierarchy relation: %s', $e->getMessage()), 1716484843, $e);
        }
    }

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
        try {
            $databaseConnection->update(
                $tableNames->hierarchyRelation(),
                $data,
                $this->getDatabaseId()
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to update hierarchy relation: %s', $e->getMessage()), 1716478609, $e);
        }
    }

    public function assignNewPosition(int $position, Connection $databaseConnection, ContentGraphTableNames $tableNames): void
    {
        try {
            $databaseConnection->update(
                $tableNames->hierarchyRelation(),
                [
                    'position' => $position
                ],
                $this->getDatabaseId()
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to update hierarchy relation: %s', $e->getMessage()), 1716485014, $e);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function getDatabaseId(): array
    {
        if (!$this->hierarchyRelationId->value) {
            throw new \RuntimeException(sprintf('Hierarchy relation was not created in the database and does not have an id: %s', json_encode([
                'parentnodeanchor' => $this->parentNodeAnchor->value,
                'childnodeanchor' => $this->childNodeAnchor->value,
                'contentstreamlayer' => $this->contentStreamLayer->value,
                'dimensionspacepointhash' => $this->dimensionSpacePointHash
            ])), 1775979706);
        }
        return [
            'id' => $this->hierarchyRelationId->value,
            'contentstreamlayer' => $this->contentStreamLayer->value,
        ];
    }
}
