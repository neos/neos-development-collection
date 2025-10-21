<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\SubtreeTagging\SoftRemoval;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/** @internal */
final readonly class ImpendingHardRemovalConflictRepository
{
    private const CONFLICT_TABLE_NAME = 'neos_neos_impending_hard_removal_conflict';

    public function __construct(
        private Connection $dbal
    ) {
    }

    public function findAllConflicts(ContentRepositoryId $contentRepositoryId): ImpendingHardRemovalConflicts
    {
        $table = self::CONFLICT_TABLE_NAME;
        $query = <<<SQL
            SELECT
                node_aggregate_id, dimension_space_points
            FROM
                {$table}
            WHERE
                content_repository_id = :contentRepositoryId
        SQL;

        $rows = $this->dbal->fetchAllAssociative($query, [
            'contentRepositoryId' => $contentRepositoryId->value,
        ]);

        /** @var array<string, ImpendingHardRemovalConflict> $conflicts */
        $conflicts = [];
        foreach ($rows as $row) {
            $nodeAggregateId = NodeAggregateId::fromString($row['node_aggregate_id']);

            if (!array_key_exists($nodeAggregateId->value, $conflicts)) {
                $conflicts[$nodeAggregateId->value] = ImpendingHardRemovalConflict::create(
                    $nodeAggregateId,
                    DimensionSpacePointSet::fromJsonString($row['dimension_space_points'])
                );
            } else {
                $conflicts[$nodeAggregateId->value] = ImpendingHardRemovalConflict::create(
                    $nodeAggregateId,
                    $conflicts[$nodeAggregateId->value]->dimensionSpacePointSet
                        ->getUnion(DimensionSpacePointSet::fromJsonString($row['dimension_space_points']))
                );
            }
        }

        return ImpendingHardRemovalConflicts::fromArray($conflicts);
    }

    public function addConflict(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
        ImpendingHardRemovalConflicts $conflicts
    ): void {
        $table = self::CONFLICT_TABLE_NAME;
        $query = <<<SQL
            SELECT
                dimension_space_points
            FROM
                {$table}
            WHERE
                content_repository_id = :contentRepositoryId
                AND workspace_name = :workspaceName
                AND node_aggregate_id = :nodeAggregateId
        SQL;

        foreach ($conflicts as $conflict) {
            if ($conflict->dimensionSpacePointSet->isEmpty()) {
                continue;
            }
            $row = $this->dbal->fetchAssociative($query, [
                'contentRepositoryId' => $contentRepositoryId->value,
                'workspaceName' => $workspaceName->value,
                'nodeAggregateId' => $conflict->nodeAggregateId->value
            ]);
            if ($row === false) {
                $this->dbal->insert($table, [
                    'content_repository_id' => $contentRepositoryId->value,
                    'workspace_name' => $workspaceName->value,
                    'node_aggregate_id' => $conflict->nodeAggregateId->value,
                    'dimension_space_points' => $conflict->dimensionSpacePointSet->toJson()
                ]);
            } else {
                $updatedDimensionSpacePoints = DimensionSpacePointSet::fromJsonString($row['dimension_space_points'])
                    ->getUnion($conflict->dimensionSpacePointSet);
                $this->dbal->update($table, [
                    'dimension_space_points' => $updatedDimensionSpacePoints->toJson()
                ], [
                    'content_repository_id' => $contentRepositoryId->value,
                    'workspace_name' => $workspaceName->value,
                ]);
            }
        }
    }

    public function pruneConflictsForWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): void
    {
        $this->dbal->delete(self::CONFLICT_TABLE_NAME, [
            'content_repository_id' => $contentRepositoryId->value,
            'workspace_name' => $workspaceName->value
        ]);
    }

    public function pruneConflictsForContentRepository(ContentRepositoryId $contentRepositoryId): void
    {
        $this->dbal->delete(self::CONFLICT_TABLE_NAME, [
            'content_repository_id' => $contentRepositoryId->value
        ]);
    }
}
