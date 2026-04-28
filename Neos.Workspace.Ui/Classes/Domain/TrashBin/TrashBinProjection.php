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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\InitiatingEventMetadata;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceBaseWorkspaceWasChanged;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceWasRemoved;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Dbal\DbalSchemaDiff;
use Neos\ContentRepository\Dbal\DbalSchemaFactory;
use Neos\EventStore\Model\Event\EventMetadata;
use Neos\EventStore\Model\EventEnvelope;
use Neos\Neos\Domain\SubtreeTagging\NeosSubtreeTag;

/**
 * @internal for communication within the Workspace UI only
 * @implements ProjectionInterface<TrashItemFinder>
 */
class TrashBinProjection implements ProjectionInterface
{
    private TrashItemFinder $trashItemFinder;

    private string $itemTableName;

    private string $workspaceHierarchyTableName;

    public function __construct(
        private readonly Connection $dbal,
        private readonly string $tableNamePrefix,
    ) {
        $this->itemTableName = $this->tableNamePrefix . '_items';
        $this->workspaceHierarchyTableName = $this->tableNamePrefix . '_workspace_hierarchy';
        $this->trashItemFinder = new TrashItemFinder($this->dbal, $this->itemTableName);
    }

    /**
     * @return void
     * @throws DBALException
     */
    public function setUp(): void
    {
        foreach ($this->determineRequiredSqlStatements() as $statement) {
            $this->dbal->executeStatement($statement);
        }
    }

    public function status(): ProjectionStatus
    {
        try {
            $this->dbal->connect();
        } catch (\Throwable $e) {
            return ProjectionStatus::error(sprintf('Failed to connect to database: %s', $e->getMessage()));
        }
        try {
            $requiredSqlStatements = $this->determineRequiredSqlStatements();
        } catch (\Throwable $e) {
            return ProjectionStatus::error(sprintf('Failed to determine required SQL statements: %s', $e->getMessage()));
        }
        if ($requiredSqlStatements !== []) {
            return ProjectionStatus::setupRequired(sprintf('The following SQL statement%s required: %s', count($requiredSqlStatements) !== 1 ? 's are' : ' is', implode(chr(10), $requiredSqlStatements)));
        }
        return ProjectionStatus::ok();
    }

    /**
     * @return array<string>
     * @throws DBALException
     * @throws SchemaException
     */
    private function determineRequiredSqlStatements(): array
    {
        $connection = $this->dbal;
        $platform = $this->dbal->getDatabasePlatform();

        $trashItemTable = new Table(
            $this->itemTableName,
            [
                DbalSchemaFactory::columnForWorkspaceName('workspace_name', $platform)->setNotNull(true),
                DbalSchemaFactory::columnForNodeAggregateId('node_aggregate_id', $platform)->setNotnull(true),
                (new Column('user_id', Type::getType(Types::STRING)))->setLength(36)->setNotnull(false),
                (new Column('delete_time', Type::getType(Types::DATETIME_IMMUTABLE)))->setNotnull(false),
                (new Column('affected_dimension_space_points', Type::getType(Types::JSON)))->setNotnull(true),
                DbalSchemaFactory::columnForDimensionSpacePointHash('affected_dimension_space_points_hash', $platform)->setNotnull(false),
            ]
        );
        $trashItemTable->setPrimaryKey(['workspace_name', 'node_aggregate_id', 'affected_dimension_space_points_hash']);
        $trashItemTable->addIndex(['workspace_name', 'delete_time'], 'by_workspace');

        $workspaceHierarchyTable = new Table(
            $this->workspaceHierarchyTableName,
            [
                DbalSchemaFactory::columnForWorkspaceName('parent_workspace_name', $platform)->setNotNull(true),
                DbalSchemaFactory::columnForNodeAggregateId('child_workspace_name', $platform)->setNotnull(true),
            ]
        );
        $workspaceHierarchyTable->setPrimaryKey(['parent_workspace_name', 'child_workspace_name']);

        $schema = DbalSchemaFactory::createSchemaWithTables($connection, [$trashItemTable, $workspaceHierarchyTable]);
        $statements = DbalSchemaDiff::determineRequiredSqlStatements($connection, $schema);

        return $statements;
    }

    public function resetState(): void
    {
        $this->dbal->exec('TRUNCATE ' . $this->itemTableName);
        $this->dbal->exec('TRUNCATE ' . $this->workspaceHierarchyTableName);
    }

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        match ($event::class) {
            SubtreeWasTagged::class => $this->whenSubtreeWasTagged($event, $eventEnvelope),
            SubtreeWasUntagged::class => $this->whenSubtreeWasUntagged($event),
            NodeAggregateWasRemoved::class => $this->whenNodeAggregateWasRemoved($event),
            WorkspaceWasCreated::class => $this->whenWorkspaceWasCreated($event),
            WorkspaceBaseWorkspaceWasChanged::class => $this->whenWorkspaceBaseWorkspaceWasChanged($event),
            WorkspaceWasRebased::class => $this->whenWorkspaceWasRebased($event),
            WorkspaceWasRemoved::class => $this->whenWorkspaceWasRemoved($event),
            WorkspaceWasPublished::class => $this->whenWorkspaceWasPublished($event),
            WorkspaceWasDiscarded::class => $this->whenWorkspaceWasDiscarded($event),
            // we don't need to handle all events
            default => null,
        };
    }

    public function getState(): TrashItemFinder
    {
        return $this->trashItemFinder;
    }

    private function whenSubtreeWasTagged(SubtreeWasTagged $event, EventEnvelope $eventEnvelope): void
    {
        if (!$event->tag->equals(NeosSubtreeTag::removed())) {
            return;
        }

        $this->createRecord(
            workspaceName: $event->workspaceName,
            nodeAggregateId: $event->nodeAggregateId,
            affectedDimensionSpacePoints: $event->affectedDimensionSpacePoints,
            eventMetadata: $eventEnvelope->event->metadata,
        );
    }

    private function whenSubtreeWasUntagged(SubtreeWasUntagged $event): void
    {
        if (!$event->tag->equals(NeosSubtreeTag::removed())) {
            return;
        }

        $this->reduceOrRemoveRecord(
            workspaceName: $event->workspaceName,
            nodeAggregateId: $event->nodeAggregateId,
            dimensionSpacePointsToReduceBy: $event->affectedDimensionSpacePoints,
        );
    }

    private function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        $this->reduceOrRemoveRecord(
            workspaceName: $event->workspaceName,
            nodeAggregateId: $event->nodeAggregateId,
            dimensionSpacePointsToReduceBy: $event->affectedCoveredDimensionSpacePoints,
        );
    }

    private function whenWorkspaceWasCreated(WorkspaceWasCreated $event): void
    {
        $this->dbal->insert(
            $this->workspaceHierarchyTableName,
            [
                'parent_workspace_name' => $event->baseWorkspaceName->value,
                'child_workspace_name' => $event->workspaceName->value,
            ]
        );
    }

    private function whenWorkspaceWasRebased(WorkspaceWasRebased $event): void
    {
        $workspaceHierarchyRecord = $this->dbal->executeQuery(
            'SELECT * FROM ' . $this->workspaceHierarchyTableName . ' WHERE child_workspace_name = :childWorkspaceName',
            [
                'childWorkspaceName' => $event->workspaceName,
            ]
        )->fetchAssociative();

        if (!$workspaceHierarchyRecord) {
            throw new \Exception('Could not resolve base workspace for workspace ' . $event->workspaceName->value, 1761035918);
        }

        $this->replaceWorkspaceEntries($event->workspaceName, WorkspaceName::fromString($workspaceHierarchyRecord['parent_workspace_name']));
    }

    private function whenWorkspaceBaseWorkspaceWasChanged(WorkspaceBaseWorkspaceWasChanged $event): void
    {
        $this->dbal->update(
            $this->workspaceHierarchyTableName,
            [
                'parent_workspace_name' => $event->baseWorkspaceName->value,
            ],
            [
                'child_workspace_name' => $event->workspaceName->value,
            ]
        );
    }

    private function whenWorkspaceWasRemoved(WorkspaceWasRemoved $event): void
    {
        $this->dbal->delete(
            $this->workspaceHierarchyTableName,
            [
                'child_workspace_name' => $event->workspaceName->value,
            ]
        );
    }

    private function whenWorkspaceWasPublished(WorkspaceWasPublished $event): void
    {
        $this->replaceWorkspaceEntries($event->sourceWorkspaceName, $event->targetWorkspaceName);
    }

    private function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event): void
    {
        $workspaceHierarchyRecord = $this->dbal->executeQuery(
            'SELECT * FROM ' . $this->workspaceHierarchyTableName . ' WHERE child_workspace_name = :childWorkspaceName',
            [
                'childWorkspaceName' => $event->workspaceName,
            ]
        )->fetchAssociative();

        if (!$workspaceHierarchyRecord) {
            throw new \Exception('Could not resolve base workspace for workspace ' . $event->workspaceName->value, 1761035918);
        }

        $this->replaceWorkspaceEntries($event->workspaceName, WorkspaceName::fromString($workspaceHierarchyRecord['parent_workspace_name']));
    }


    private function createRecord(
        WorkspaceName $workspaceName,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePointSet $affectedDimensionSpacePoints,
        ?EventMetadata $eventMetadata,
    ): void {
        $this->dbal->insert(
            $this->itemTableName,
            [
                'workspace_name' => $workspaceName->value,
                'node_aggregate_id' => $nodeAggregateId->value,
                'user_id' => $eventMetadata?->get(InitiatingEventMetadata::INITIATING_USER_ID),
                'delete_time' => $eventMetadata?->get(InitiatingEventMetadata::INITIATING_TIMESTAMP)
                    ? (
                        (\DateTimeImmutable::createFromFormat(
                            \DateTimeInterface::ATOM,
                            $eventMetadata->get(InitiatingEventMetadata::INITIATING_TIMESTAMP)
                        ) ?: null)
                            ?->setTimezone(new \DateTimeZone('UTC'))
                            ->format('Y-m-d H:i:s')
                    ) : null,
                'affected_dimension_space_points' => $affectedDimensionSpacePoints->toJson(),
                'affected_dimension_space_points_hash' => $this->getDimensionSpacePointSetHash($affectedDimensionSpacePoints),
            ]
        );
    }

    private function reduceOrRemoveRecord(
        WorkspaceName $workspaceName,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePointSet $dimensionSpacePointsToReduceBy,
    ): void {
        $presentRecords = $this->dbal->executeQuery(
            'SELECT * FROM ' . $this->itemTableName . ' WHERE workspace_name = :workspaceName
            AND node_aggregate_id = :nodeAggregateId',
            [
                'workspaceName' => $workspaceName->value,
                'nodeAggregateId' => $nodeAggregateId->value,
            ],
        )->fetchAllAssociative();

        foreach ($presentRecords as $presentRecord) {
            $previousAffectedDimensionSpacePoints = DimensionSpacePointSet::fromJsonString($presentRecord['affected_dimension_space_points']);
            $newAffectedDimensionSpacePoints = $previousAffectedDimensionSpacePoints->getDifference($dimensionSpacePointsToReduceBy);
            if ($newAffectedDimensionSpacePoints->isEmpty()) {
                $this->dbal->executeStatement(
                    'DELETE FROM ' . $this->itemTableName . ' WHERE workspace_name = :workspaceName
                        AND node_aggregate_id = :nodeAggregateId
                        AND affected_dimension_space_points_hash = :affectedDimensionSpacePointsHash',
                    [
                        'workspaceName' => $workspaceName->value,
                        'nodeAggregateId' => $nodeAggregateId->value,
                        'affectedDimensionSpacePointsHash' => $presentRecord['affected_dimension_space_points_hash']
                    ]
                );
            } else {
                $this->dbal->update(
                    $this->itemTableName,
                    [
                        'affected_dimension_space_points' => $newAffectedDimensionSpacePoints->toJson(),
                        'affected_dimension_space_points_hash' => $this->getDimensionSpacePointSetHash($newAffectedDimensionSpacePoints),
                    ],
                    [
                        'workspace_name' => $workspaceName->value,
                        'node_aggregate_id' => $nodeAggregateId->value,
                        'affected_dimension_space_points_hash' => $presentRecord['affected_dimension_space_points_hash']
                    ]
                );
            }
        }
    }

    private function replaceWorkspaceEntries(WorkspaceName $workspaceName, WorkspaceName $baseWorkspaceName): void
    {
        $this->dbal->executeStatement(
            'DELETE FROM ' . $this->itemTableName . ' WHERE workspace_name = :workspaceName',
            [
                'workspaceName' => $workspaceName->value,
            ]
        );

        $copyStatement = <<<SQL
            INSERT INTO {$this->itemTableName} (
                workspace_name,
                node_aggregate_id,
                affected_dimension_space_points_hash,
                user_id,
                delete_time,
                affected_dimension_space_points
            )
            SELECT
                '{$workspaceName->value}' AS workspace_name,
                i.node_aggregate_id,
                i.affected_dimension_space_points_hash,
                i.user_id,
                i.delete_time,
                i.affected_dimension_space_points
            FROM
                {$this->itemTableName} i
                WHERE i.workspace_name = :baseWorkspaceName
        SQL;
        $this->dbal->executeStatement(
            $copyStatement,
            [
                'baseWorkspaceName' => $baseWorkspaceName->value,
            ]
        );
    }

    private function getDimensionSpacePointSetHash(DimensionSpacePointSet $dimensionSpacePointSet): string
    {
        return \md5($dimensionSpacePointSet->toJson());
    }
}
