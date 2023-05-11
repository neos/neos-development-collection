<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Service;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjection;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepositoryRegistry\Command\MigrateEventsCommandController;
use Neos\ContentRepositoryRegistry\Factory\EventStore\DoctrineEventStoreFactory;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Neos\Neos\FrontendRouting\Projection\DocumentUriPathProjection;

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

    public function __construct(
        private readonly Projections $projections,
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly ContentRepository $contentRepository,
        private readonly EventStoreInterface $eventStore,
        private readonly Connection $connection,
    ) {
    }

    /**
     * Adds affectedDimensionSpacePoints to NodePropertiesWereSet event, by replaying the content graph
     * and then reading the dimension space points for the relevant NodeAggregate.
     *
     * Needed for #4265: https://github.com/neos/neos-development-collection/issues/4265
     *
     * Included in May 2023 - before Neos 9.0 Beta 1.
     *
     * @param \Closure $outputFn
     * @return void
     */
    public function fillAffectedDimensionSpacePointsInNodePropertiesWereSet(\Closure $outputFn)
    {

        $backupEventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId)
            . '_bak_' . date('Y_m_d_H_i_s');
        $outputFn('Backup: copying events table to %s', [$backupEventTableName]);
        $this->copyEventTable($backupEventTableName);

        $outputFn('Backup completed. Resetting Graph Projection.');
        $this->contentRepository->resetProjectionState(ContentGraphProjection::class);

        $contentGraphProjection = $this->projections->get(ContentGraphProjection::class);
        $contentGraph = $contentGraphProjection->getState();
        assert($contentGraph instanceof ContentGraphInterface);

        $streamName = VirtualStreamName::all();
        $eventStream = $this->eventStore->load($streamName);
        foreach ($eventStream as $eventEnvelope) {
            if ($eventEnvelope->event->type->value === 'NodePropertiesWereSet') {
                $eventData = json_decode($eventEnvelope->event->data->value, true);
                if (!isset($eventData['affectedDimensionSpacePoints'])) {
                    // Replay the projection until before the current NodePropertiesWereSet event
                    $contentGraphProjection->catchUp(
                        $eventStream->withMaximumSequenceNumber($eventEnvelope->sequenceNumber->previous()),
                        $this->contentRepository
                    );

                    // now we can ask the NodeAggregate (read model) for the covered DSPs.
                    $nodeAggregate = $contentGraph->findNodeAggregateById(
                        ContentStreamId::fromString($eventData['contentStreamId']),
                        NodeAggregateId::fromString($eventData['nodeAggregateId'])
                    );
                    $affectedDimensionSpacePoints = $nodeAggregate->getCoverageByOccupant(
                        OriginDimensionSpacePoint::fromArray($eventData['originDimensionSpacePoint'])
                    );

                    // ... and update the event
                    $eventData['affectedDimensionSpacePoints'] = $affectedDimensionSpacePoints->jsonSerialize();
                    $outputFn(
                        'Rewriting %s: (%s, Origin: %s) => Affected: %s',
                        [
                            $eventEnvelope->sequenceNumber->value,
                            $eventEnvelope->event->type->value,
                            json_encode($eventData['originDimensionSpacePoint']),
                            json_encode($eventData['affectedDimensionSpacePoints'])
                        ]
                    );
                    $this->updateEvent($eventEnvelope->sequenceNumber, $eventData);
                }
            }
        }

        $outputFn('Rewriting completed. Now catching up the GraphProjection to final state.');
        $contentGraphProjection->catchUp($eventStream, $this->contentRepository);

        if ($this->projections->has(DocumentUriPathProjection::class)) {
            $outputFn('Found DocumentUriPathProjection. Will replay this, as it relies on the updated affectedDimensionSpacePoints');
            $documentUriPathProjection = $this->projections->get(DocumentUriPathProjection::class);
            $documentUriPathProjection->reset();
            $documentUriPathProjection->catchUp($eventStream, $this->contentRepository);
        }

        $outputFn('All done.');
    }


    private function updateEvent(SequenceNumber $sequenceNumber, array $eventData)
    {
        $eventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId);
        $this->connection->beginTransaction();
        $this->connection->executeStatement(
            'UPDATE ' . $eventTableName . ' SET payload=:payload WHERE sequencenumber=:sequenceNumber',
            [
                'payload' => json_encode($eventData),
                'sequenceNumber' => $sequenceNumber->value
            ]
        );
        $this->connection->commit();
    }

    private function copyEventTable(string $backupEventTableName)
    {
        $eventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId);
        $this->connection->executeStatement(
            'CREATE TABLE ' . $backupEventTableName . ' AS
            SELECT *
            FROM ' . $eventTableName
        );
    }
}
