<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\PublishedEvents;

/**
 * Collection of {@see CommandHookInterface} instances, functioning as a delegating command hook implementation
 *
 * @implements \IteratorAggregate<CommandHookInterface>
 * @api
 */
final readonly class CommandHooks implements CommandHookInterface, \IteratorAggregate, \Countable
{
    /**
     * @var array<CommandHookInterface>
     */
    private array $commandHooks;

    private function __construct(
        CommandHookInterface ...$commandHooks
    ) {
        $this->commandHooks = $commandHooks;
    }

    /**
     * @param array<CommandHookInterface> $commandHooks
     */
    public static function fromArray(array $commandHooks): self
    {
        return new self(...$commandHooks);
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public function getIterator(): \Traversable
    {
        yield from $this->commandHooks;
    }

    public function count(): int
    {
        return count($this->commandHooks);
    }

    public function onBeforeHandle(CommandInterface $command): CommandInterface
    {
        foreach ($this->commandHooks as $commandHook) {
            $command = $commandHook->onBeforeHandle($command);
        }
        return $command;
    }

    public function onAfterHandle(CommandInterface $command, PublishedEvents $events): Commands
    {
        $commands = Commands::createEmpty();
        foreach ($this->commandHooks as $commandHook) {
            $commands = $commands->merge($commandHook->onAfterHandle($command, $events));
        }
        return $commands;
    }
}
