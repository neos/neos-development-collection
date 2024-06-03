<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;

/**
 * Implementation Detail of {@see ContentRepository::handle}, which does the command dispatching to the different
 * {@see CommandHandlerInterface} implementation.
 *
 * @internal
 */
final class CommandBus
{
    /**
     * @var CommandHandlerInterface[]
     */
    private array $handlers;

    public function __construct(CommandHandlerInterface ...$handlers)
    {
        $this->handlers = $handlers;
    }

    public function handle(CommandInterface $command, CommandHandlingDependencies $commandHandlingDependencies): EventsToPublish
    {
        // TODO fail if multiple handlers can handle the same command
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($command)) {
                return $handler->handle($command, $commandHandlingDependencies);
            }
        }
        throw new \RuntimeException(sprintf('No handler found for Command "%s"', get_debug_type($command)), 1649582778);
    }
}
