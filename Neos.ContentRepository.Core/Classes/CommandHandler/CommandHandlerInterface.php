<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;

/**
 * Common interface for all Content Repository command handlers
 *
 * @internal no public API, because commands are no extension points of the CR
 */
interface CommandHandlerInterface
{
    public function canHandle(CommandInterface|RebasableToOtherWorkspaceInterface $command): bool;

    public function handle(CommandInterface|RebasableToOtherWorkspaceInterface $command): EventsToPublish;
}
