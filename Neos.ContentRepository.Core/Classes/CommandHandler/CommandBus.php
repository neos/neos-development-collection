<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\RebaseableCommand;

/**
 * Implementation Detail of {@see ContentRepository::handle}, which does the command dispatching to the different
 * {@see CommandHandlerInterface} implementation.
 *
 * @internal
 */
final readonly class CommandBus
{
    /**
     * @var CommandHandlerInterface[]
     */
    private array $handlers;

    /**
     * @var CommandSerializerInterface[]
     */
    private array $serializers;

    public function __construct(
        // todo pass $commandHandlingDependencies in each command handler instead of into the commandBus
        private CommandHandlingDependencies $commandHandlingDependencies,
        CommandHandlerInterface ...$handlers
    ) {
        $this->handlers = $handlers;
        $serializers = [];
        foreach ($handlers as $handler) {
            if ($handler instanceof CommandSerializerInterface) {
                $serializers[] = $handler;
            }
        }
        $this->serializers = $serializers;
    }

    /**
     * @return EventsToPublish|\Generator<int, EventsToPublish>
     */
    public function handle(PublicCommandInterface|RebasableToOtherWorkspaceInterface $command): EventsToPublish|\Generator
    {
        $possiblySerializedCommand = $command;
        if (!$command instanceof RebasableToOtherWorkspaceInterface) {
            foreach ($this->serializers as $serializer) {
                if ($serializer->canSerialize($command)) {
                    $possiblySerializedCommand = $serializer->serialize($command, $this->commandHandlingDependencies);
                    break;
                }
            }
        }

        // multiple handlers must not handle the same command
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($possiblySerializedCommand)) {
                $eventsToPublish = $handler->handle($possiblySerializedCommand, $this->commandHandlingDependencies);

                if (!$eventsToPublish instanceof EventsToPublish) {
                    // generator todo?
                    return $eventsToPublish;
                }

                if ($possiblySerializedCommand instanceof RebasableToOtherWorkspaceInterface) {
                    return new EventsToPublish(
                        $eventsToPublish->streamName,
                        RebaseableCommand::enrichWithCommand(
                            $possiblySerializedCommand,
                            $eventsToPublish->events
                        ),
                        $eventsToPublish->expectedVersion
                    );
                }

                return $eventsToPublish;
            }
        }
        throw new \RuntimeException(sprintf('No handler found for Command "%s"', get_debug_type($possiblySerializedCommand)), 1649582778);
    }

    public function withAdditionalHandlers(CommandHandlerInterface ...$handlers): self
    {
        return new self(
            $this->commandHandlingDependencies,
            ...$this->handlers,
            ...$handlers,
        );
    }
}
