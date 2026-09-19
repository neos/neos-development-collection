<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\EmbedsNodeAggregateId;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Neos\Neos\Domain\Service\WorkspacePublishingService;
use Neos\Neos\PendingChangesProjection\Change;
use Neos\Neos\PendingChangesProjection\ChangeFinder;
use PHPUnit\Framework\Assert;

/**
 * Step implementations for tests inside Neos.Neos
 *
 * @internal only for behat tests within the Neos.Neos package
 */
trait PendingChangesTrait
{
    /**
     * @var array<\Neos\EventStore\Model\Event>
     */
    private array $pendingChanges_publishedEvents = [];

    /**
     * @var array<\Neos\EventStore\Model\Event>
     */
    private array $pendingChanges_remainingEvents = [];

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract private function getObject(string $className): object;

    /**
     * @Then I expect to have the following changes in workspace :workspace:
     */
    public function iExpectTheChangeProjectionToHaveTheFollowingChangesInContentStream(TableNode $table, string $workspace)
    {
        // forwards compatible adjustment, we make the test assertions on workspace names even tough we store the data in content streams
        $workspaceInstance = $this->currentContentRepository->findWorkspaceByName(WorkspaceName::fromString($workspace));
        Assert::assertNotNull($workspaceInstance, 'workspace doesnt exist');

        $changeFinder = $this->currentContentRepository->projectionState(ChangeFinder::class);
        $changes = iterator_to_array($changeFinder->findByContentStreamId($workspaceInstance->currentContentStreamId));

        $actualChangesTable = array_map(static fn (Change $change) => [
            'nodeAggregateId' => $change->nodeAggregateId->value,
            'created' => (string)(int)$change->created,
            'changed' => (string)(int)$change->changed,
            'moved' => (string)(int)$change->moved,
            'deleted' => (string)(int)$change->deleted,
            'originDimensionSpacePoint' => $change->originDimensionSpacePoint?->toJson(),
        ], iterator_to_array($changes));

        $expectedChangesWithNormalisedJson = array_map(
            fn (array $row) => [
                ...$row,
                'originDimensionSpacePoint' => ($originDimensionSpacePoint = json_decode($row['originDimensionSpacePoint'], true)) !== null
                    ? OriginDimensionSpacePoint::fromArray($originDimensionSpacePoint)->toJson()
                    : null,
            ],
            $table->getHash()
        );

        // assertEqualsCanonicalizing removes keys by using sort recursively that's why we sort manually
        // sort by unique index to make rows easier comparable when diffing
        $sortRows = fn ($rowA, $rowB) => strcmp($rowA['nodeAggregateId'], $rowB['nodeAggregateId']) ?: strcmp($rowA['originDimensionSpacePoint'] ?? '', $rowB['originDimensionSpacePoint'] ?? '');
        usort($actualChangesTable, $sortRows);
        usort($expectedChangesWithNormalisedJson, $sortRows);

        Assert::assertEquals($expectedChangesWithNormalisedJson, $actualChangesTable, 'Mismatch of changes');
    }

    /**
     * @Then I expect to have no changes in workspace :workspace
     */
    public function iExpectToHaveNoChangesInWorkspace(string $workspace)
    {
        // forwards compatible adjustment, we make the test assertions on workspace names even tough we store the data in content streams
        $workspaceInstance = $this->currentContentRepository->findWorkspaceByName(WorkspaceName::fromString($workspace));
        Assert::assertNotNull($workspaceInstance, 'workspace doesnt exist');

        $changeFinder = $this->currentContentRepository->projectionState(ChangeFinder::class);
        $changes = iterator_to_array($changeFinder->findByContentStreamId($workspaceInstance->currentContentStreamId));

        Assert::assertEmpty($changes, 'No changes expected, got: ' . json_encode($changes, JSON_PRETTY_PRINT));
    }

    /**
     * @Then I expect the ChangeProjection to have no changes in :contentStreamId
     * @deprecated assertions to check that the internal change projection is really empty for that stream. Can be removed when migrating to workspaces
     */
    public function iExpectTheChangeProjectionToHaveNoChangesInContentStream(string $contentStreamId)
    {
        $changeFinder = $this->currentContentRepository->projectionState(ChangeFinder::class);
        $changes = iterator_to_array($changeFinder->findByContentStreamId(ContentStreamId::fromString($contentStreamId)));

        Assert::assertEmpty($changes, 'No changes expected, got: ' . json_encode($changes, JSON_PRETTY_PRINT));
    }

    /**
     * @Then I expect for the site :siteNodeAggregateId to have :count publishable changes in workspace :workspace
     */
    public function iExpectTheSiteToHaveXPublishableChanges(string $siteNodeAggregateId, int $count, string $workspace): void
    {
        // the actual information for this resides in the Ui Neos\Neos\Ui\ContentRepository\Service\WorkspaceService in combination with client js calculation logic
        // in the future the WorkspacePublishingService must be able to calculate the pending changes based on the publishing scope with hierarchy
        $actualCount = $this->getObject(WorkspacePublishingService::class)->countPendingWorkspaceChanges($this->currentContentRepository->id, WorkspaceName::fromString($workspace));
        Assert::assertEquals($count, $actualCount);
    }

    /**
     * @Then I publish the :expectedCount changes in document :documentNodeAggregateId from workspace :workspace to :expectedTarget
     * @Then I publish the :expectedCount changes in site :siteNodeAggregateId from workspace :workspace to :expectedTarget
     */
    public function iPublishTheDocumentFromWorkspace(string $workspace, int $expectedCount, string $expectedTarget, ?string $documentNodeAggregateId = null, ?string $siteNodeAggregateId = null): void
    {
        $sourceWorkspace = $this->currentContentRepository->findWorkspaceByName(WorkspaceName::fromString($workspace));
        Assert::assertEquals($expectedTarget, $sourceWorkspace->baseWorkspaceName->value);

        $nextSequenceNumber = iterator_to_array($this->getEventStore()->load(VirtualStreamName::all())->backwards()->limit(1))[0]->sequenceNumber->next();

        $workspacePublishingService = $this->getObject(WorkspacePublishingService::class);

        $actualResult = match(true) {
            $siteNodeAggregateId !== null => $workspacePublishingService->publishChangesInSite($this->currentContentRepository->id, WorkspaceName::fromString($workspace), NodeAggregateId::fromString($siteNodeAggregateId)),
            $documentNodeAggregateId !== null => $workspacePublishingService->publishChangesInDocument($this->currentContentRepository->id, WorkspaceName::fromString($workspace), NodeAggregateId::fromString($documentNodeAggregateId))
        };

        Assert::assertEquals($sourceWorkspace->baseWorkspaceName, $actualResult->targetWorkspaceName);
        Assert::assertEquals($expectedCount, $actualResult->numberOfPublishedChanges);

        /** @var \Neos\ContentRepository\Core\EventStore\EventNormalizer $eventNormaliser */
        $eventNormaliser = \Neos\Utility\ObjectAccess::getProperty($this->currentContentRepository, 'eventNormalizer', true);

        $targetWorkspace = $this->currentContentRepository->findWorkspaceByName($sourceWorkspace->baseWorkspaceName);

        $publishCorrelationId = null;

        // refetch workspace with new cs id
        $sourceWorkspace = $this->currentContentRepository->findWorkspaceByName(WorkspaceName::fromString($workspace));
        $remainingEvents = [];
        foreach ($this->getEventStore()->load(ContentStreamEventStreamName::fromContentStreamId($sourceWorkspace->currentContentStreamId)->getEventStreamName())->withMinimumSequenceNumber($nextSequenceNumber) as $eventEnvelope) {
            assert($eventEnvelope->event->correlationId !== null);
            $publishCorrelationId ??= $eventEnvelope->event->correlationId;
            if ($eventEnvelope->event->correlationId->value !== $publishCorrelationId->value) {
                break;
            }
            if (in_array(EmbedsNodeAggregateId::class, class_implements($eventNormaliser->getEventClassName($eventEnvelope->event)))) {
                $remainingEvents[] = $eventEnvelope->event;
            }
        }
        $this->pendingChanges_remainingEvents = $remainingEvents;

        $publishedEvents = [];
        foreach ($this->getEventStore()->load(ContentStreamEventStreamName::fromContentStreamId($targetWorkspace->currentContentStreamId)->getEventStreamName())->withMinimumSequenceNumber($nextSequenceNumber) as $eventEnvelope) {
            assert($eventEnvelope->event->correlationId !== null);
            $publishCorrelationId ??= $eventEnvelope->event->correlationId;
            if ($eventEnvelope->event->correlationId->value !== $publishCorrelationId->value) {
                break;
            }
            if (in_array(EmbedsNodeAggregateId::class, class_implements($eventNormaliser->getEventClassName($eventEnvelope->event)))) {
                $publishedEvents[] = $eventEnvelope->event;
            }
        }
        $this->pendingChanges_publishedEvents = $publishedEvents;
    }

    /**
     * @Then I expect the publishing of document :documentNodeAggregateId from workspace :workspace to fail
     * @Then I expect the publishing of site :siteNodeAggregateId from workspace :workspace to fail
     */
    public function iExpectThePublicationOfTheDocumentFromWorkspaceToFail(string $workspace, ?string $documentNodeAggregateId = null, ?string $siteNodeAggregateId = null): void
    {
        $workspacePublishingService = $this->getObject(WorkspacePublishingService::class);
        $this->tryCatchingExceptions(fn () =>
            match(true) {
                $siteNodeAggregateId !== null => $workspacePublishingService->publishChangesInSite($this->currentContentRepository->id, WorkspaceName::fromString($workspace), NodeAggregateId::fromString($siteNodeAggregateId)),
                $documentNodeAggregateId !== null => $workspacePublishingService->publishChangesInDocument($this->currentContentRepository->id, WorkspaceName::fromString($workspace), NodeAggregateId::fromString($documentNodeAggregateId))
            }
        );
        Assert::assertNotNull($this->lastCaughtException, 'Expected an exception but none was thrown');
    }


    /**
     * @Then I expect that the following node events have been published
     */
    public function iExpectTheFollowingNodeEventsToBePublished(TableNode $expectedNodeEvents): void
    {
        self::assertEventsTableMatchesExpected($expectedNodeEvents->getHash(), $this->pendingChanges_publishedEvents);
    }

    /**
     * @Then I expect that the following node events are kept as remainder
     */
    public function iExpectTheFollowingNodeEventsToBeKept(TableNode $expectedNodeEvents): void
    {
        self::assertEventsTableMatchesExpected($expectedNodeEvents->getHash(), $this->pendingChanges_remainingEvents);
    }

    /**
     * @param array<\Neos\EventStore\Model\Event> $actualEvents
     */
    private function assertEventsTableMatchesExpected(array $expectedEventsTable, array $actualEvents)
    {
        $expectedEventsTableNormalised = array_map(fn (array $row) => [
            ...$row,
            'event payload' => json_decode($row['event payload'], true)
        ], $expectedEventsTable);

        $actualEventsTable = [];
        foreach ($actualEvents as $i => $actualEvent) {
            $actualPayload = json_decode($actualEvent->data->value, true);
            $expectedPayload = $expectedEventsTableNormalised[$i]['event payload'] ?? [];
            if ($expectedPayload !== [] && array_diff_key($expectedPayload, $actualPayload) === []) {
                // to simplify assertions we allow to only specify certain keys that will be compared instead of having to snapshot the full payload
                $actualPayload = array_intersect_key($actualPayload, $expectedPayload);
            }
            $actualEventsTable[] = [
                'type' => $actualEvent->type->value,
                'event payload' => $actualPayload
            ];
        }

        Assert::assertSame($expectedEventsTableNormalised, $actualEventsTable);
    }
}
