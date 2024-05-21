<?php

/*
 * This file is part of the Neos.ContentRepository.BehavioralTests package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Command;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\EventPersister;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Command\ForkContentStream;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;
use Neos\ContentRepositoryRegistry\Factory\EventStore\DoctrineEventStoreFactory;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

class PerformanceMeasurementService implements ContentRepositoryServiceInterface
{
    private ContentStreamId $contentStreamId;
    private WorkspaceName $workspaceName;
    private DimensionSpacePointSet $dimensionSpacePoints;
    private ContentStreamEventStreamName $contentStreamEventStream;

    public function __construct(
        private readonly EventPersister $eventPersister,
        private readonly ContentRepository $contentRepository,
        private readonly Connection $dbal,
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly Projections $projections,
    ) {
        $this->contentStreamId = contentStreamId::fromString('cs-identifier');
        $this->workspaceName = WorkspaceName::fromString('some-workspace');
        $this->dimensionSpacePoints = new DimensionSpacePointSet([
            DimensionSpacePoint::fromArray(['language' => 'mul']),
            DimensionSpacePoint::fromArray(['language' => 'de']),
            DimensionSpacePoint::fromArray(['language' => 'gsw']),
            DimensionSpacePoint::fromArray(['language' => 'en']),
            DimensionSpacePoint::fromArray(['language' => 'fr'])
        ]);

        $this->contentStreamEventStream = ContentStreamEventStreamName::fromContentStreamId(
            $this->contentStreamId
        );
    }

    public function removeEverything(): void
    {
        $eventTableName = DoctrineEventStoreFactory::databaseTableName($this->contentRepositoryId);
        $this->dbal->executeStatement('TRUNCATE ' . $this->dbal->quoteIdentifier($eventTableName));
        $this->projections->resetAll();
    }

    public function createNodesForPerformanceTest(int $nodesPerLevel, int $levels): void
    {
        $this->contentRepository->handle(CreateRootWorkspace::create(
            WorkspaceName::forLive(),
            WorkspaceTitle::fromString('live'),
            WorkspaceDescription::fromString(''),
            $this->contentStreamId
        ));

        $rootNodeAggregateId = nodeAggregateId::fromString('lady-eleonode-rootford');
        $rootNodeAggregateWasCreated = new RootNodeAggregateWithNodeWasCreated(
            $this->workspaceName,
            $this->contentStreamId,
            $rootNodeAggregateId,
            NodeTypeName::fromString('Neos.ContentRepository:Root'),
            $this->dimensionSpacePoints,
            NodeAggregateClassification::CLASSIFICATION_ROOT,
        );

        $this->eventPersister->publishEvents($this->contentRepository, new EventsToPublish(
            $this->contentStreamEventStream->getEventStreamName(),
            Events::with($rootNodeAggregateWasCreated),
            ExpectedVersion::ANY()
        ));

        #$time = microtime(true);
        $sumSoFar = 0;
        $events = [];
        $this->createHierarchy($rootNodeAggregateId, 1, $levels, $nodesPerLevel, $sumSoFar, $events);
        $this->eventPersister->publishEvents($this->contentRepository, new EventsToPublish(
            $this->contentStreamEventStream->getEventStreamName(),
            Events::fromArray($events),
            ExpectedVersion::ANY()
        ));
        echo $sumSoFar;
        #$this->outputLine(microtime(true) - $time . ' elapsed');
    }


    /**
     * @throws \Throwable
     * @param array<int,EventInterface> $events
     */
    private function createHierarchy(
        nodeAggregateId $parentNodeAggregateId,
        int $currentLevel,
        int $maximumLevel,
        int $numberOfNodes,
        int &$sumSoFar,
        array &$events,
    ): void {
        if ($currentLevel <= $maximumLevel) {
            for ($i = 0; $i < $numberOfNodes; $i++) {
                $nodeAggregateId = nodeAggregateId::create();
                $events[] = new NodeAggregateWithNodeWasCreated(
                    $this->workspaceName,
                    $this->contentStreamId,
                    $nodeAggregateId,
                    NodeTypeName::fromString('Neos.ContentRepository:Testing'),
                    OriginDimensionSpacePoint::fromArray(['language' => 'mul']),
                    InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings($this->dimensionSpacePoints),
                    $parentNodeAggregateId,
                    null,
                    SerializedPropertyValues::createEmpty(),
                    NodeAggregateClassification::CLASSIFICATION_REGULAR,
                );
                $sumSoFar++;
                $this->createHierarchy(
                    $nodeAggregateId,
                    $currentLevel + 1,
                    $maximumLevel,
                    $numberOfNodes,
                    $sumSoFar,
                    $events
                );
            }
        }
    }

    public function forkContentStream(): void
    {
        $this->contentRepository->handle(ForkContentStream::create(
            ContentStreamId::create(),
            $this->contentStreamId,
        ));
    }
}
