<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository;


use Neos\ContentRepository\CommandHandler\CommandBus;
use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\CommandHandler\CommandResult;
use Neos\ContentRepository\EventStore\EventPersister;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Content\ContentGraphProjection;
use Neos\ContentRepository\Projection\ProjectionInterface;
use Neos\ContentRepository\Projection\Projections;
use Neos\ContentRepository\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Projection\Workspace\WorkspaceProjection;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\EventStore\SetupResult;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Neos\EventStore\ProvidesSetupInterface;

/**
 * Main Entry Point to the system. Encapsulates the full event-sourced Content Repository.
 *
 * Use this to:
 * - set up the necessary database tables and contents via {@see ContentRepository::setUp()}
 * - send commands to the system (to mutate state) via {@see ContentRepository::handle()}
 * - access projection state (to read state) via {@see ContentRepository::projectionState()}
 * - catch up projections via {@see ContentRepository::catchUpProjection()}
 */
final class ContentRepository
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly EventStoreInterface $eventStore,
        private readonly Projections $projections,
        private readonly EventPersister $eventPersister,
    )
    {
    }

    /**
     * The only API to send commands (mutation intentions) to the system.
     *
     * The system is ASYNCHRONOUS by default, so that means the projection is not directly up to date. If you
     * need to be synchronous, call {@see CommandResult::block()} on the returned CommandResult - then the system
     * waits until the projections are up to date.
     *
     * @param CommandInterface $command
     * @return CommandResult
     */
    public function handle(CommandInterface $command): CommandResult
    {
        // the commands only calculate which events they want to have published, but do not do the publishing themselves.
        $eventsToPublish = $this->commandBus->handle($command, $this);

        return $this->eventPersister->publishEvents($eventsToPublish);
    }

    /**
     * @template T of ProjectionState
     * @param class-string<ProjectionInterface<T>> $projectionClassName
     * @return T
     */
    public function projectionState(string $projectionClassName): ProjectionStateInterface
    {
        return $this->projections->get($projectionClassName)->getState();
    }

    /**
     * @template T of ProjectionState
     * @param class-string<ProjectionInterface<T>> $projectionClassName
     */
    public function catchUpProjection(string $projectionClassName): void
    {
        $projection = $this->projections->get($projectionClassName);
        // TODO allow custom stream name per projection
        $streamName = VirtualStreamName::all();
        $eventStream = $this->eventStore->load($streamName);
        $projection->catchUp($eventStream);
    }

    public function setUp(): SetupResult
    {
        if ($this->eventStore instanceof ProvidesSetupInterface) {
            $result = $this->eventStore->setup();
            // TODO better result object
            if ($result->errors !== []) {
                return $result;
            }
        }
        foreach ($this->projections as $projection) {
            $projection->setUp();
        }
        return SetupResult::success('done');
    }

    public function resetProjectionStates(): void
    {
        foreach ($this->projections as $projection) {
            $projection->reset();
        }
    }

    /** TODO  public function getNodeTypeManager() */

    public function getContentGraph(): ContentGraphInterface
    {
        return $this->projectionState(ContentGraphProjection::class);
    }

    public function getWorkspaceFinder(): WorkspaceFinder
    {
        return $this->projectionState(WorkspaceProjection::class);
    }
}
