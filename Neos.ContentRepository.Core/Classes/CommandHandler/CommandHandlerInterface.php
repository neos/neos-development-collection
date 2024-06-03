<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;

/**
 * Common interface for all Content Repository command handlers
 *
 * Note: The Content Repository instance is passed to the handle() method for it to do soft-constraint checks or
 * trigger "sub commands"
 *
 * @internal no public API, because commands are no extension points of the CR
 */
interface CommandHandlerInterface
{
    public function canHandle(CommandInterface $command): bool;
    public function handle(CommandInterface $command, CommandHandlingDependencies $commandHandlingDependencies): EventsToPublish;
}
