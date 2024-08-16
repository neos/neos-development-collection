<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Service;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Command\CopyNodesRecursively;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetSerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\Command\MigrateEventsCommandController;
use Neos\ContentRepositoryRegistry\Factory\EventStore\DoctrineEventStoreFactory;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStream\VirtualStreamName;

/**
 * Content Repository service to perform migrations of events.
 *
 * Each function is used here for a specific migration. The migrations are only useful for production
 * workloads which have events prior to the code change.
 *
 * @internal this is currently only used by the {@see MigrateEventsCommandController}
 */
final class EventMigrationService implements ContentRepositoryServiceInterface
{
    /** @var array<int, true> */
    private array $eventsModified = [];

    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly EventStoreInterface $eventStore,
        private readonly Connection $connection,
    ) {
    }

    /**
     * The following things have to be migrated:
     *
     * - `NodePropertiesWereSet` `payload`
     * - `SetSerializedNodeProperties` in `metadata`
     *
     * example:
     *     "propertyValues":{"tagName":{"value":null,"type":"string"}}
     * ->
     *     "propertyValues":[],"propertiesToUnset":["tagName"]
     *
     * Here we just have to omit the null values because its a new node either way:
     *
     * - `NodeAggregateWithNodeWasCreated` `payload`
     * - `CreateNodeAggregateWithNodeAndSerializedProperties` in `metadata`
     *
     * example:
     *     "initialPropertyValues":{"title":{"value":"Blog","type":"string"},"titleOverride":{"value":null,"type":"string"},"uriPathSegment":{"value":"blog","type":"string"}}
     * ->
     *     "initialPropertyValues":{"title":{"value":"Blog","type":"string"},"uriPathSegment":{"value":"blog","type":"string"}}
     *
     * - `CopyNodesRecursively` in `metadata`
     *
     * example (somewhere deep recursive in $nodeTreeToInsert):
     *     "propertyValues":{"senderName":{"value":null,"type":"string"}}}
     * ->
     *     "propertyValues":{}
     *
     * Also the basically impossible state of "initialPropertyValues":{"tagName":null} and "propertyValues":{"tagName":null} will be migrated.
     *
     * Needed for #4322: https://github.com/neos/neos-development-collection/pull/4322
     *
     * Included in February 2024 - before final Neos 9.0 release
     *
     * @param \Closure $outputFn
     * @return void
     */
    public function migratePropertiesToUnset(\Closure $outputFn): void
    {
        $this->eventsModified = [];
        $warnings = 0;

        $backupEventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId)
            . '_bkp_' . date('Y_m_d_H_i_s');
        $outputFn(sprintf('Backup: copying events table to %s', $backupEventTableName));

        $this->copyEventTable($backupEventTableName);

        $streamName = VirtualStreamName::all();
        $eventStream = $this->eventStore->load($streamName);
        foreach ($eventStream as $eventEnvelope) {
            $outputRewriteNotice = fn(string $message) => $outputFn(sprintf('%s@%s %s', $eventEnvelope->sequenceNumber->value, $eventEnvelope->event->type->value, $message));

            // migrate payload
            if ($eventEnvelope->event->type->value === 'NodePropertiesWereSet') {
                $eventData = self::decodeEventPayload($eventEnvelope);
                if (isset($eventData['propertiesToUnset'])) {
                    // is already new event type with field
                    continue;
                }

                // separate and omit null setters to unsets:
                $unsetProperties = [];
                foreach ($eventData['propertyValues'] as $key => $value) {
                    if ($value === null || $value['value'] === null) {
                        $unsetProperties[] = $key;
                        unset($eventData['propertyValues'][$key]);
                    }
                }
                $eventData['propertiesToUnset'] = $unsetProperties;

                if (count($unsetProperties)) {
                    // in case we just set `propertiesToUnset` to an empty array we dont display output.
                    $outputRewriteNotice(sprintf('Payload: Migrated %d $unsetProperties', count($unsetProperties)));
                }
                $this->updateEventPayload($eventEnvelope->sequenceNumber, $eventData);

                // optionally also migrate metadata
                $eventMetaData = $eventEnvelope->event->metadata?->value;
                // optionally also migrate metadata
                if (!isset($eventMetaData['commandClass'])) {
                    continue;
                }

                if ($eventMetaData['commandClass'] !== SetSerializedNodeProperties::class) {
                    $warnings++;
                    $outputFn(sprintf('WARNING: Cannot migrate event metadata of %s as commandClass %s was not expected.', $eventEnvelope->event->type->value, $eventMetaData['commandClass']));
                    continue;
                }

                // separate and omit null setters to unsets:
                $unsetProperties = [];
                foreach ($eventMetaData['commandPayload']['propertyValues'] as $key => $value) {
                    if ($value === null || $value['value'] === null) {
                        $unsetProperties[] = $key;
                        unset($eventMetaData['commandPayload']['propertyValues'][$key]);
                    }
                }
                $eventMetaData['commandPayload']['propertiesToUnset'] = $unsetProperties;

                if (count($unsetProperties)) {
                    // in case we just set `propertiesToUnset` to an empty array we dont display output.
                    $outputRewriteNotice(sprintf('Metadata: Migrated %d $unsetProperties', count($unsetProperties)));
                }
                $this->updateEventMetaData($eventEnvelope->sequenceNumber, $eventMetaData);
                continue;
            }

            if ($eventEnvelope->event->type->value === 'NodeAggregateWithNodeWasCreated') {
                $eventData = self::decodeEventPayload($eventEnvelope);
                // omit null setters
                $propertiesWithNullValues = 0;
                foreach ($eventData['initialPropertyValues'] as $key => $value) {
                    if ($value === null || $value['value'] === null) {
                        $propertiesWithNullValues++;
                        unset($eventData['initialPropertyValues'][$key]);
                    }
                }
                if ($propertiesWithNullValues) {
                    $outputRewriteNotice(sprintf('Payload: Removed %d $initialPropertyValues', $propertiesWithNullValues));
                    $this->updateEventPayload($eventEnvelope->sequenceNumber, $eventData);
                }

                $eventMetaData = $eventEnvelope->event->metadata?->value;
                // optionally also migrate metadata
                if (!isset($eventMetaData['commandClass'])) {
                    continue;
                }

                if ($eventMetaData['commandClass'] === CreateNodeAggregateWithNodeAndSerializedProperties::class) {
                    // omit null setters
                    $propertiesWithNullValues = 0;
                    foreach ($eventMetaData['commandPayload']['initialPropertyValues'] as $key => $value) {
                        if ($value === null || $value['value'] === null) {
                            $propertiesWithNullValues++;
                            unset($eventMetaData['commandPayload']['initialPropertyValues'][$key]);
                        }
                    }
                    if ($propertiesWithNullValues) {
                        $outputRewriteNotice(sprintf('Metadata: Removed %d $initialPropertyValues', $propertiesWithNullValues));
                        $this->updateEventMetaData($eventEnvelope->sequenceNumber, $eventMetaData);
                    }
                } elseif ($eventMetaData['commandClass'] === CopyNodesRecursively::class) {
                    // nodes can be also created on copy, and in $nodeTreeToInsert, we have to also omit null values.
                    // NodeDuplicationCommandHandler::createEventsForNodeToInsert

                    $removeNullValuesRecursively = function (array &$nodeSubtreeSnapshotData, int &$propertiesWithNullValues, $fn) {
                        foreach ($nodeSubtreeSnapshotData['propertyValues'] as $key => $value) {
                            if ($value === null || $value['value'] === null) {
                                $propertiesWithNullValues++;
                                unset($nodeSubtreeSnapshotData['propertyValues'][$key]);
                            }
                        }
                        foreach ($nodeSubtreeSnapshotData['childNodes'] as &$childNode) {
                            $fn($childNode, $propertiesWithNullValues, $fn);
                        }
                    };

                    $propertiesWithNullValues = 0;
                    $removeNullValuesRecursively($eventMetaData['commandPayload']['nodeTreeToInsert'], $propertiesWithNullValues, $removeNullValuesRecursively);
                    if ($propertiesWithNullValues) {
                        $outputRewriteNotice(sprintf('Metadata: Removed %d $propertyValues from $nodeTreeToInsert', $propertiesWithNullValues));
                        $this->updateEventMetaData($eventEnvelope->sequenceNumber, $eventMetaData);
                    }
                } else {
                    $warnings++;
                    $outputFn(sprintf('WARNING: Cannot migrate event metadata of %s as commandClass %s was not expected.', $eventEnvelope->event->type->value, $eventMetaData['commandClass']));
                }
            }
        }

        if (!count($this->eventsModified)) {
            $outputFn('Migration was not necessary.');
            return;
        }

        $outputFn();
        $outputFn(sprintf('Migration applied to %s events.', count($this->eventsModified)));
        $outputFn('Please replay your content-graph projection via `flow cr:projectionReplay contentGraph`.');
        if ($warnings) {
            $outputFn(sprintf('WARNING: Finished but %d warnings emitted.', $warnings));
        }
    }


    /**
     * Adds a dummy workspace name to the events meta-data, so it can be rebased
     *
     * The value of the payload for `workspaceName` is only required to successfully instantiate a command by its metadata.
     * This is only necessary for rebasing where directly override the workspace name to the target one.
     * Thus, we simply enter a dummy string "missing:{contentStreamId}".
     *
     * Needed for #4708: https://github.com/neos/neos-development-collection/pull/4708
     *
     * Included in March 2024 - before final Neos 9.0 release
     *
     * @param \Closure $outputFn
     * @return void
     */
    public function migrateMetaDataToWorkspaceName(\Closure $outputFn): void
    {
        $this->eventsModified = [];

        $backupEventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId)
            . '_bkp_' . date('Y_m_d_H_i_s');
        $outputFn('Backup: copying events table to %s', [$backupEventTableName]);

        $this->copyEventTable($backupEventTableName);

        $streamName = VirtualStreamName::all();
        $eventStream = $this->eventStore->load($streamName);
        foreach ($eventStream as $eventEnvelope) {
            $outputRewriteNotice = fn(string $message) => $outputFn(sprintf('%s@%s %s', $eventEnvelope->sequenceNumber->value, $eventEnvelope->event->type->value, $message));
            $eventMetaData = $eventEnvelope->event->metadata?->value;

            if (!$eventMetaData || !($commandClassName = $eventMetaData['commandClass'] ?? null)) {
                continue;
            }

            /**
             * Nearly all implementations of {@see RebasableToOtherWorkspaceInterface::createCopyForWorkspace()} have to migrate.
             * The following commands all require the `$workspaceName` field and have no `$contentStreamId`.
             * The commands AddDimensionShineThrough and MoveDimensionSpacePoint are exceptions to the rule, which don't
             * require workspaces but still operate on content streams. {@link https://github.com/neos/neos-development-collection/issues/4942}
             */
            if (!in_array($commandClassName, [
                CreateNodeAggregateWithNodeAndSerializedProperties::class,
                DisableNodeAggregate::class,
                EnableNodeAggregate::class,
                CopyNodesRecursively::class,
                SetSerializedNodeProperties::class,
                MoveNodeAggregate::class,
                SetSerializedNodeReferences::class,
                RemoveNodeAggregate::class,
                ChangeNodeAggregateName::class,
                ChangeNodeAggregateType::class,
                CreateNodeVariant::class,
                CreateRootNodeAggregateWithNode::class,
                UpdateRootNodeAggregateDimensions::class,
            ])) {
                continue;
            }

            if (isset($eventMetaData['commandPayload']['workspaceName'])) {
                continue;
            }

            // ... and update the event
            // the payload is only used for rebasing where we override the workspace either way:
            $eventMetaData['commandPayload']['workspaceName'] = 'missing:' . ($eventMetaData['commandPayload']['contentStreamId'] ?? '');
            unset($eventMetaData['commandPayload']['contentStreamId']);

            $outputRewriteNotice(sprintf('Metadata: Added `workspaceName`'));

            $this->updateEventMetaData($eventEnvelope->sequenceNumber, $eventMetaData);
        }

        if (!count($this->eventsModified)) {
            $outputFn('Migration was not necessary.');
            return;
        }

        $outputFn();
        $outputFn(sprintf('Migration applied to %s events.', count($this->eventsModified)));
    }

    /**
     *  Adds the "workspaceName" to the data of all content stream related events
     *
     *  Needed for feature "Add workspaceName to relevant events": https://github.com/neos/neos-development-collection/issues/4996
     *
     *  Included in May 2024 - before final Neos 9.0 release
     *
     * @param \Closure $outputFn
     * @return void
     */
    public function migratePayloadToWorkspaceName(\Closure $outputFn): void
    {

        $backupEventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId) . '_bkp_' . date('Y_m_d_H_i_s');
        $outputFn('Backup: copying events table to %s', [$backupEventTableName]);
        $this->copyEventTable($backupEventTableName);

        $numberOfMigratedEvents = 0;
        $workspaceNamesByContentStreamId = [];

        foreach ($this->eventStore->load(VirtualStreamName::all()) as $eventEnvelope) {
            $eventType = $eventEnvelope->event->type->value;
            if (in_array($eventType, ['RootWorkspaceWasCreated', 'WorkspaceBaseWorkspaceWasChanged', 'WorkspaceWasCreated', 'WorkspaceWasDiscarded', 'WorkspaceWasPartiallyDiscarded', 'WorkspaceWasRebased'], true)) {
                $eventData = self::decodeEventPayload($eventEnvelope);
                $workspaceNamesByContentStreamId[$eventData['newContentStreamId']] = $eventData['workspaceName'];
                continue;
            }
            if (in_array($eventType, ['WorkspaceWasPartiallyPublished', 'WorkspaceWasPublished'], true)) {
                $eventData = self::decodeEventPayload($eventEnvelope);
                $workspaceNamesByContentStreamId[$eventData['newSourceContentStreamId']] = $eventData['sourceWorkspaceName'];
                continue;
            }
            if (!in_array($eventType, ['DimensionShineThroughWasAdded', 'DimensionSpacePointWasMoved', 'NodeAggregateWithNodeWasCreated', 'NodeAggregateWasDisabled', 'NodeAggregateWasEnabled', 'NodePropertiesWereSet', 'NodeAggregateWasMoved', 'NodeReferencesWereSet', 'NodeAggregateWasRemoved', 'NodeAggregateNameWasChanged', 'NodeAggregateTypeWasChanged', 'NodeGeneralizationVariantWasCreated', 'NodePeerVariantWasCreated', 'NodeSpecializationVariantWasCreated', 'RootNodeAggregateDimensionsWereUpdated', 'RootNodeAggregateWithNodeWasCreated', 'SubtreeWasTagged', 'SubtreeWasUntagged'], true)) {
                continue;
            }
            $eventData = self::decodeEventPayload($eventEnvelope);
            $workspaceName = $workspaceNamesByContentStreamId[$eventData['contentStreamId']] ?? null;
            if ($workspaceName === null) {
                $workspaceName = WorkspaceName::fromString('missing');
            }
            $this->updateEventPayload($eventEnvelope->sequenceNumber, [...$eventData, 'workspaceName' => $workspaceName]);
            $numberOfMigratedEvents++;
        }
        if ($numberOfMigratedEvents === 0) {
            $outputFn('Migration was not necessary.');
            return;
        }

        $outputFn();
        $outputFn(sprintf('Migration applied to %s events.', $numberOfMigratedEvents));
    }

    /**
     * Rewrites all workspaceNames, that are not matching new constraints.
     *
     * Needed for feature "Stabilize WorkspaceName value object": https://github.com/neos/neos-development-collection/pull/5193
     *
     * Included in August 2024 - before final Neos 9.0 release
     *
     * @param \Closure $outputFn
     * @return void
     */
    public function migratePayloadToValidWorkspaceNames(\Closure $outputFn): void
    {
        $backupEventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId) . '_bkp_' . date('Y_m_d_H_i_s');
        $outputFn('Backup: copying events table to %s', [$backupEventTableName]);
        $this->copyEventTable($backupEventTableName);

        $eventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId);
        $this->connection->beginTransaction();
        $statementWorkspaceName = <<<SQL
                UPDATE {$eventTableName}
                SET
                    payload = JSON_SET(
                          payload,
                          '$.workspaceName',
                          IF(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.workspaceName')) REGEXP '^[a-z0-9][a-z0-9\-]{0,35}$',
                            LOWER(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.workspaceName'))),
                            MD5(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.workspaceName')))
                          )
                        )
                WHERE
                  JSON_EXTRACT(payload, '$.workspaceName') IS NOT NULL
                  AND BINARY JSON_UNQUOTE(JSON_EXTRACT(payload, '$.workspaceName')) NOT REGEXP '^[a-z0-9][a-z0-9\-]{0,35}$'
            SQL;
        $affectedRowsWorkspaceName = $this->connection->executeStatement($statementWorkspaceName);

        $statementBaseWorkspaceName = <<<SQL
                UPDATE {$eventTableName}
                SET
                    payload = JSON_SET(
                          payload,
                          '$.baseWorkspaceName',
                          IF(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.baseWorkspaceName')) REGEXP '^[a-z0-9][a-z0-9\-]{0,35}$',
                            LOWER(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.baseWorkspaceName'))),
                            MD5(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.baseWorkspaceName')))
                          )
                        )
                WHERE
                  JSON_EXTRACT(payload, '$.baseWorkspaceName') IS NOT NULL
                  AND BINARY JSON_UNQUOTE(JSON_EXTRACT(payload, '$.baseWorkspaceName')) NOT REGEXP '^[a-z0-9][a-z0-9\-]{0,35}$'
            SQL;
        $affectedRowsBaseWorkspaceName = $this->connection->executeStatement($statementBaseWorkspaceName);

        $sourceWorkspaceNameStatement = <<<SQL
                UPDATE {$eventTableName}
                SET
                    payload = JSON_SET(
                          payload,
                          '$.sourceWorkspaceName',
                          IF(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.sourceWorkspaceName')) REGEXP '^[a-z0-9][a-z0-9\-]{0,35}$',
                            LOWER(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.sourceWorkspaceName'))),
                            MD5(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.sourceWorkspaceName')))
                          )
                        )
                WHERE
                  JSON_EXTRACT(payload, '$.sourceWorkspaceName') IS NOT NULL
                  AND BINARY JSON_UNQUOTE(JSON_EXTRACT(payload, '$.sourceWorkspaceName')) NOT REGEXP '^[a-z0-9][a-z0-9\-]{0,35}$'
            SQL;
        $sourceWorkspaceAffectedRows = $this->connection->executeStatement($sourceWorkspaceNameStatement);

        $targetWorkspaceNameStatement = <<<SQL
                UPDATE {$eventTableName}
                SET
                    payload = JSON_SET(
                          payload,
                          '$.targetWorkspaceName',
                          IF(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.targetWorkspaceName')) REGEXP '^[a-z0-9][a-z0-9\-]{0,35}$',
                            LOWER(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.targetWorkspaceName'))),
                            MD5(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.targetWorkspaceName')))
                          )
                        )
                WHERE
                  JSON_EXTRACT(payload, '$.targetWorkspaceName') IS NOT NULL
                  AND BINARY JSON_UNQUOTE(JSON_EXTRACT(payload, '$.targetWorkspaceName')) NOT REGEXP '^[a-z0-9][a-z0-9\-]{0,35}$'
            SQL;
        $targetWorkspaceAffectedRows = $this->connection->executeStatement($targetWorkspaceNameStatement);
        $this->connection->commit();

        if ($affectedRowsWorkspaceName === 0 && $affectedRowsBaseWorkspaceName === 0 && $sourceWorkspaceAffectedRows === 0 && $targetWorkspaceAffectedRows === 0) {
            $outputFn('Migration was not necessary.');
            return;
        }

        $outputFn();
        $outputFn(sprintf('Migration applied to %s events and changed the workspaceName.', $affectedRowsWorkspaceName));
        $outputFn(sprintf('Migration applied to %s events and changed the baseWorkspaceName.', $affectedRowsBaseWorkspaceName));
        $outputFn(sprintf('Migration applied to %s events and changed the sourceWorkspaceName.', $sourceWorkspaceAffectedRows));
        $outputFn(sprintf('Migration applied to %s events and changed the targetWorkspaceName.', $targetWorkspaceAffectedRows));
        $outputFn(sprintf('You need to replay your projection for workspaces. Please run: ./flow cr:projectionreplay --projection=workspace'));
    }

    /** ------------------------ */

    /**
     * @return array<string, mixed>
     */
    private static function decodeEventPayload(EventEnvelope $eventEnvelope): array
    {
        try {
            return json_decode($eventEnvelope->event->data->value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to JSON-decode event payload of event #%d: %s', $eventEnvelope->sequenceNumber->value, $e->getMessage()), 1715951538, $e);
        }
    }

    /**
     * @param array<mixed> $payload
     */
    private function updateEventPayload(SequenceNumber $sequenceNumber, array $payload): void
    {
        $eventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId);
        $this->connection->beginTransaction();
        $this->connection->executeStatement(
            'UPDATE ' . $eventTableName . ' SET payload=:payload WHERE sequencenumber=:sequenceNumber',
            [
                'sequenceNumber' => $sequenceNumber->value,
                'payload' => json_encode($payload),
            ]
        );
        $this->connection->commit();
        $this->eventsModified[$sequenceNumber->value] = true;
    }

    /**
     * @param array<mixed> $eventMetaData
     */
    private function updateEventMetaData(SequenceNumber $sequenceNumber, array $eventMetaData): void
    {
        $eventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId);
        $this->connection->beginTransaction();
        $this->connection->executeStatement(
            'UPDATE ' . $eventTableName . ' SET metadata=:metadata WHERE sequencenumber=:sequenceNumber',
            [
                'sequenceNumber' => $sequenceNumber->value,
                'metadata' => json_encode($eventMetaData),
            ]
        );
        $this->connection->commit();
        $this->eventsModified[$sequenceNumber->value] = true;
    }

    private function copyEventTable(string $backupEventTableName): void
    {
        $eventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId);
        $this->connection->executeStatement(
            'CREATE TABLE ' . $backupEventTableName . ' AS
            SELECT *
            FROM ' . $eventTableName
        );
    }
}
