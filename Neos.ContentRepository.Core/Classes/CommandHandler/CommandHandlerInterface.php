<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;

/**
 * Common interface for all Content Repository command handlers
 *
 * The {@see CommandHandlingDependencies} are available during handling to do soft-constraint checks
 *
 * @internal no public API, because commands are no extension points of the CR
 */
interface CommandHandlerInterface
{
    public function canHandle(CommandInterface|RebasableToOtherWorkspaceInterface $command): bool;

    /**
     * "simple" command handlers return EventsToPublish directly
     *
     * For the case of the workspace command handler that need to publish to many streams and "close" the content-stream directly,
     * it's allowed to yield the events to interact with the control flow of event publishing.
     *
     * @return EventsToPublish|\Generator<int, EventsToPublish>
     */
    public function handle(CommandInterface|RebasableToOtherWorkspaceInterface $command, CommandHandlingDependencies $commandHandlingDependencies): EventsToPublish|\Generator;
}
