<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Service;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Command\CopyNodesRecursively;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjection;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceProjection;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepositoryRegistry\Command\MigrateEventsCommandController;
use Neos\ContentRepositoryRegistry\Factory\EventStore\DoctrineEventStoreFactory;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\SequenceNumber;
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
     * Included in February 2023 - before final Neos 9.0 release
     *
     * @param \Closure $outputFn
     * @return void
     */
    public function migratePropertiesToUnset(\Closure $outputFn)
    {
        $this->eventsModified = [];
        $warnings = 0;

        $backupEventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId)
            . '_bak_' . date('Y_m_d_H_i_s');
        $outputFn(sprintf('Backup: copying events table to %s', $backupEventTableName));

        $this->copyEventTable($backupEventTableName);

        $streamName = VirtualStreamName::all();
        $eventStream = $this->eventStore->load($streamName);
        foreach ($eventStream as $eventEnvelope) {
            $outputRewriteNotice = fn(string $message) => $outputFn(sprintf('%s@%s %s', $eventEnvelope->sequenceNumber->value, $eventEnvelope->event->type->value, $message));

            // migrate payload
            if ($eventEnvelope->event->type->value === 'NodePropertiesWereSet') {
                $eventData = json_decode($eventEnvelope->event->data->value, true);
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
                $eventData = json_decode($eventEnvelope->event->data->value, true);
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
     * Add the workspace name to the events meta-data, so it can be replayed.
     *
     * Needed for #4708: https://github.com/neos/neos-development-collection/pull/4708
     *
     * Included in February 2023 - before Neos 9.0 Beta 3.
     *
     * @param \Closure $outputFn
     * @return void
     */
    public function fillWorkspaceNameInCommandPayloadOfEventMetaData(\Closure $outputFn)
    {

        $backupEventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId)
            . '_bak_' . date('Y_m_d_H_i_s');
        $outputFn('Backup: copying events table to %s', [$backupEventTableName]);

        $this->copyEventTable($backupEventTableName);

        $outputFn('Backup completed. Resetting WorkspaceProjection.');
        $this->contentRepository->resetProjectionState(WorkspaceProjection::class);

        $workspaceProjection = $this->projections->get(WorkspaceProjection::class);
        $workspaceFinder = $workspaceProjection->getState();
        assert($workspaceFinder instanceof WorkspaceFinder);

        $streamName = VirtualStreamName::all();
        $eventStream = $this->eventStore->load($streamName);
        foreach ($eventStream as $eventEnvelope) {
            if (!$eventEnvelope->event->metadata) {
                continue;
            }
            $eventMetaData = $eventEnvelope->event->metadata->value;

            if (!in_array($eventMetaData['commandClass'] ?? null, [
                // todo extend list
                SetSerializedNodeProperties::class
            ])) {
                continue;
            }

            if (!isset($eventMetaData['commandPayload']['contentStreamId']) || isset($eventMetaData['commandPayload']['workspaceName'])) {
                continue;
            }

            // Replay the projection until before the current event
            $this->contentRepository->catchUpProjection(WorkspaceProjection::class, CatchUpOptions::create(maximumSequenceNumber: $eventEnvelope->sequenceNumber->previous()));

            // now we can ask the read model
            $workspace = $workspaceFinder->findOneByCurrentContentStreamId(ContentStreamId::fromString($eventMetaData['commandPayload']['contentStreamId']));

            // ... and update the event
            if (!$workspace) {
                // todo does not exist, but as the value is not important when rebasing, as bernhard said, we will just enter a dummy.
                $eventMetaData['commandPayload']['workspaceName'] = 'dummystring';
            } else {
                $eventMetaData['commandPayload']['workspaceName'] = $workspace->workspaceName->value;
            }
            unset($eventMetaData['commandPayload']['contentStreamId']);

            $outputFn(
                'Rewriting %s: (%s, ContentStreamId: %s) => WorkspaceName: %s',
                [
                    $eventEnvelope->sequenceNumber->value,
                    $eventEnvelope->event->type->value,
                    $eventMetaData['commandPayload']['contentStreamId'],
                    $eventMetaData['commandPayload']['workspaceName']
                ]
            );

            $this->updateEventMetaData($eventEnvelope->sequenceNumber, $eventMetaData);
        }

        $outputFn('Rewriting completed. Now catching up the WorkspaceProjection to final state.');
        $this->contentRepository->catchUpProjection(WorkspaceProjection::class, CatchUpOptions::create());

        $outputFn('All done.');
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
