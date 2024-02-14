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
use Neos\ContentRepository\Core\Projection\Projections;
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
    private bool $eventsTableWasUpdated = false;

    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly ContentRepository $contentRepository,
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
     * Needed for #4322: https://github.com/neos/neos-development-collection/pull/4322
     *
     * Included in February 2023 - before final Neos 9.0 release
     *
     * @param \Closure $outputFn
     * @return void
     */
    public function migratePropertiesToUnset(\Closure $outputFn)
    {
        $this->eventsTableWasUpdated = false;

        $backupEventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId)
            . '_bak_' . date('Y_m_d_H_i_s');
        $outputFn(sprintf('Backup: copying events table to %s', $backupEventTableName));

        $this->copyEventTable($backupEventTableName);

        $streamName = VirtualStreamName::all();
        $eventStream = $this->eventStore->load($streamName);
        foreach ($eventStream as $eventEnvelope) {
            $outputRewriteNotice = fn(string $message) => $outputFn(sprintf('%s@%s %s', $eventEnvelope->event->type->value, $eventEnvelope->sequenceNumber->value, $message));

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

                $outputRewriteNotice(sprintf('Migrated %d $unsetProperties', count($unsetProperties)));
                $this->updateEventPayload($eventEnvelope->sequenceNumber, $eventData);

                // optionally also migrate metadata
                $eventMetaData = $eventEnvelope->event->metadata?->value;
                // optionally also migrate metadata
                if (!isset($eventMetaData['commandClass'])) {
                    continue;
                }

                if ($eventMetaData['commandClass'] !== SetSerializedNodeProperties::class) {
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

                $outputRewriteNotice(sprintf('Metadata: Migrated %d $unsetProperties', count($unsetProperties)));
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
                    $outputRewriteNotice(sprintf('Removed %d $initialPropertyValues', $propertiesWithNullValues));
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
                    $outputFn(sprintf('WARNING: Cannot migrate event metadata of %s as commandClass %s was not expected.', $eventEnvelope->event->type->value, $eventMetaData['commandClass']));
                }
            }
        }

        if (!$this->eventsTableWasUpdated) {
            $outputFn('Migration was not necessary. All done.');
            return;
        }

        $outputFn('Rewriting completed. Replaying ContentGraph Projection.');
        $this->contentRepository->resetProjectionState(ContentGraphProjection::class);
        $this->contentRepository->catchUpProjection(ContentGraphProjection::class, CatchUpOptions::create());

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
        $this->eventsTableWasUpdated = true;
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
        $this->eventsTableWasUpdated = true;
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
