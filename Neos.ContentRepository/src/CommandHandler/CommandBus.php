<?php

declare(strict_types=1);

namespace Neos\ContentRepository\CommandHandler;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\EventStore\EventsToPublish;

/**
 * Implementation Detail of the {@see ContentRepository}, which does the command dispatching to the different
 * {@see CommandHandlerInterface} implementation.
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

    public function handle(CommandInterface $command, ContentRepository $contentRepository): EventsToPublish
    {
        // TODO fail if multiple handlers can handle the same command
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($command)) {
                return $handler->handle($command, $contentRepository);
            }
        }
        throw new \RuntimeException(sprintf('No handler found for Command "%s"', get_debug_type($command)), 1649582778);
    }
}
