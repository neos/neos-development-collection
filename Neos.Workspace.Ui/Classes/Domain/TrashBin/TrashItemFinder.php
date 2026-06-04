<?php

/*
 * This file is part of the Neos.Workspace.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\Domain\TrashBin;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\Security\Dto\UserId;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @internal for communication within the Workspace UI only
 */
class TrashItemFinder implements ProjectionStateInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $itemTableName,
    ) {
    }

    public function findItem(
        WorkspaceName $workspaceName,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePoint $dimensionSpacePoint
    ): ?TrashItem {
        $records = $this->connection->executeQuery(
            'SELECT * FROM ' . $this->itemTableName . '
                WHERE workspace_name = :workspaceName
                AND node_aggregate_id = :nodeAggregateId',
            [
                'workspaceName' => $workspaceName->value,
                'nodeAggregateId' => $nodeAggregateId->value,
            ]
        )->fetchAllAssociative();

        foreach ($this->mapRecordsToTrashItems($records) as $item) {
            if ($item->affectedDimensionSpacePoints->contains($dimensionSpacePoint)) {
                return $item;
            }
        }

        return null;
    }

    public function findItemsByWorkspaceNameWithParameters(
        WorkspaceName $workspaceName,
        TrashBinSorting $sorting,
        TrashBinPagination $pagination,
        ?NodeAggregateIds $filterToNodeAggregateIds,
    ): TrashItems {
        $query = 'SELECT * FROM ' . $this->itemTableName . ' WHERE workspace_name = :workspaceName ' . (
                $filterToNodeAggregateIds ? ' AND node_aggregate_id IN (:nodeAggregateIds) ' : ''
            ) . '
                ORDER BY ' . match ($sorting->propertyName) {
                TrashBinSortingPropertyName::SORTING_PROPERTY_DELETE_TIME => 'delete_time',
            } . ' ' . match ($sorting->direction) {
                TrashBinSortingDirection::SORTING_DESCENDING => 'DESC',
                TrashBinSortingDirection::SORTING_ASCENDING => 'ASC',
            };
        if ($pagination->limit) {
            $query .= ' LIMIT ' . $pagination->limit;
        }
        if ($pagination->offset) {
            $query .= ' OFFSET ' . $pagination->offset;
        }

        $records = $this->connection->executeQuery(
            $query,
            [
                'workspaceName' => $workspaceName->value,
                'nodeAggregateIds' => $filterToNodeAggregateIds?->toStringArray(),
            ],
            [
                'nodeAggregateIds' => ArrayParameterType::STRING
            ]
        )->fetchAllAssociative();

        return $this->mapRecordsToTrashItems($records);
    }

    public function countItemsByWorkspaceName(
        WorkspaceName $workspaceName,
        ?NodeAggregateIds $filterToNodeAggregateIds
    ): int {
        $query = 'SELECT count(*) count  FROM ' . $this->itemTableName .
                 ' WHERE workspace_name = :workspaceName ' . (
                    $filterToNodeAggregateIds ? ' AND node_aggregate_id IN (:nodeAggregateIds) ' : ''
                );

        $records = $this->connection->executeQuery(
            $query,
            [
                'workspaceName' => $workspaceName->value,
                'nodeAggregateIds' => $filterToNodeAggregateIds?->toStringArray(),
            ],
            [
                'nodeAggregateIds' => ArrayParameterType::STRING
            ]
        )->fetchAllAssociative();

        return $records[0]['count'];
    }

    /**
     * @param array<int,array<string,mixed>> $records
     */
    private function mapRecordsToTrashItems(array $records): TrashItems
    {
        return TrashItems::list(...array_map(
            fn (array $record): TrashItem => new TrashItem(
                nodeAggregateId: NodeAggregateId::fromString($record['node_aggregate_id']),
                userId: isset($record['user_id']) ? UserId::fromString($record['user_id']) : null,
                deleteTime: isset($record['delete_time']) ? (\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $record['delete_time'], new \DateTimeZone('UTC')) ?: null) : null,
                affectedDimensionSpacePoints: DimensionSpacePointSet::fromJsonString($record['affected_dimension_space_points']),
            ),
            $records,
        ));
    }
}
