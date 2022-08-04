<?php

declare(strict_types=1);

namespace Neos\ContentRepository\CommandHandler;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\EventStore\EventsToPublish;

/**
 * Common interface for all Content Repository command handlers
 *
 * Note: The Content Repository instance is passed to the handle() method for it to do soft-constraint checks or
 * trigger "sub commands"
 */
interface CommandHandlerInterface
{
    public function canHandle(CommandInterface $command): bool;
    public function handle(CommandInterface $command, ContentRepository $contentRepository): EventsToPublish;
}
