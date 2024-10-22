<?php

/*
 * This file is part of the Neos.ContentRepository.Core package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core;

use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjection;
use Neos\ContentRepository\Core\CommandHandler\CommandBus;
use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamStatus;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\EventStore\Helper\InMemoryEventStore;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\Event\Version;
use Neos\EventStore\Model\Events;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\EventStore\Model\EventStream\VirtualStreamName;

/**
 * An adapter to provide aceess to read projection data and delegate (sub) commands
 *
 * @internal only command handlers are provided with this via the
 * @see ContentRepository::handle()
 */
final class CommandHandlingDependencies
{
    /**
     * WorkspaceName->value to ContentGraphInterface
     * @var array<string, ContentGraphInterface>
     */
    private array $overriddenContentGraphInstances = [];

    private InMemoryEventStore|null $inMemoryStoreForSimulation = null;

    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly CommandBus $commandBus,
        private readonly EventNormalizer $eventNormalizer,
        // todo use GraphProjectionInterface!!!
        private readonly DoctrineDbalContentGraphProjection $contentRepositoryProjection,
        private readonly ContentGraphReadModelInterface $contentGraphReadModel
    ) {
    }


    public function handle(CommandInterface $command): void
    {
        if ($this->inMemoryStoreForSimulation !== null) {
            $this->hanldeInSimulation($command, $this->inMemoryStoreForSimulation);
        } else {
            $this->contentRepository->handle($command);
        }
    }

    /**
     * @template T
     * @param \Closure(): T $fn
     * @return T
     */
    public function inSimulation(\Closure $fn, InMemoryEventStore $inMemoryEventStore): mixed
    {
        if ($this->inMemoryStoreForSimulation) {
            throw new \RuntimeException();
        }
        $this->inMemoryStoreForSimulation = $inMemoryEventStore;
        try {
            return $this->contentRepositoryProjection->inSimulation($fn);
        } finally {
            $this->inMemoryStoreForSimulation = null;
        }
    }

    private function hanldeInSimulation(CommandInterface $command, InMemoryEventStore $inMemoryEventStore): void
    {
        $eventsToPublish = $this->commandBus->handle($command, $this);

        if ($eventsToPublish->events->isEmpty()) {
            return;
        }

        // the following logic could also be done in an AppEventStore::commit method (being called
        // directly from the individual Command Handlers).
        $normalizedEvents = Events::fromArray(
            $eventsToPublish->events->map($this->eventNormalizer->normalize(...))
        );

        $commitResult = $inMemoryEventStore->commit(
            $eventsToPublish->streamName,
            $normalizedEvents,
            ExpectedVersion::ANY() // The version of the stream in the IN MEMORY event store does not matter to us,
        // because this is only used in memory during the partial publish or rebase operation; so it cannot be written to
            // concurrently.
            // HINT: We cannot use $eventsToPublish->expectedVersion, because this is based on the PERSISTENT event stream (having different numbers)
        );


        $eventStream = $inMemoryEventStore->load(VirtualStreamName::all())->withMinimumSequenceNumber(
            // fetch all events that were now committed. Plus one because the first sequence number is one too otherwise we get one event to many.
            // (all elephants shall be placed shamefully placed on my head)
            SequenceNumber::fromInteger($commitResult->highestCommittedSequenceNumber->value - $eventsToPublish->events->count() + 1)
        );

        foreach ($eventStream as $eventEnvelope) {
            $event = $this->eventNormalizer->denormalize($eventEnvelope->event);

            if (!$this->contentRepositoryProjection->canHandle($event)) {
                continue;
            }

            $this->contentRepositoryProjection->apply($event, $eventEnvelope);
        }
    }

    public function getContentStreamVersion(ContentStreamId $contentStreamId): Version
    {
        $contentStream = $this->contentGraphReadModel->findContentStreamById($contentStreamId);
        if ($contentStream === null) {
            throw new \InvalidArgumentException(sprintf('Failed to find content stream with id "%s"', $contentStreamId->value), 1716902051);
        }
        return $contentStream->version;
    }

    public function contentStreamExists(ContentStreamId $contentStreamId): bool
    {
        return $this->contentGraphReadModel->findContentStreamById($contentStreamId) !== null;
    }

    public function getContentStreamStatus(ContentStreamId $contentStreamId): ContentStreamStatus
    {
        $contentStream = $this->contentGraphReadModel->findContentStreamById($contentStreamId);
        if ($contentStream === null) {
            throw new \InvalidArgumentException(sprintf('Failed to find content stream with id "%s"', $contentStreamId->value), 1716902219);
        }
        return $contentStream->status;
    }

    public function findWorkspaceByName(WorkspaceName $workspaceName): ?Workspace
    {
        return $this->contentGraphReadModel->findWorkspaceByName($workspaceName);
    }

    /**
     * @throws WorkspaceDoesNotExist if the workspace does not exist
     */
    public function getContentGraph(WorkspaceName $workspaceName): ContentGraphInterface
    {
        if (isset($this->overriddenContentGraphInstances[$workspaceName->value])) {
            return $this->overriddenContentGraphInstances[$workspaceName->value];
        }
        $workspace = $this->contentGraphReadModel->findWorkspaceByName($workspaceName);
        if ($workspace === null) {
            throw WorkspaceDoesNotExist::butWasSupposedTo($workspaceName);
        }
        return $this->contentGraphReadModel->buildContentGraph($workspace->workspaceName, $workspace->currentContentStreamId);
    }

    /**
     * Stateful (dirty) override of the chosen ContentStreamId for a given workspace, it applies within the given closure.
     * Implementations must ensure that requesting the contentStreamId for this workspace will resolve to the given
     * override ContentStreamId and vice versa resolving the WorkspaceName from this ContentStreamId should result in the
     * given WorkspaceName within the closure.
     *
     * @internal Used in write operations applying commands to a contentstream that will have WorkspaceName in the future
     * but doesn't have one yet.
     */
    public function overrideContentStreamId(WorkspaceName $workspaceName, ContentStreamId $contentStreamId, \Closure $fn): void
    {
        if (isset($this->overriddenContentGraphInstances[$workspaceName->value])) {
            throw new \RuntimeException('Contentstream override for this workspace already in effect, nesting not allowed.', 1715170938);
        }

        $contentGraph = $this->contentGraphReadModel->buildContentGraph($workspaceName, $contentStreamId);
        $this->overriddenContentGraphInstances[$workspaceName->value] = $contentGraph;

        try {
            $fn();
        } finally {
            unset($this->overriddenContentGraphInstances[$workspaceName->value]);
        }
    }

    /**
     * Fixme only required to build the possible catchup hooks
     */
    public function getContentRepository(): ContentRepository
    {
        return $this->contentRepository;
    }
}
