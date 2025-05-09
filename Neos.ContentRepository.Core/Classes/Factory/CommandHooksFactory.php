<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Factory;

use Neos\ContentRepository\Core\CommandHandler\CommandHooks;

/**
 * @internal
 */
final readonly class CommandHooksFactory
{
    /**
     * @var array<CommandHookFactoryInterface>
     */
    private array $commandHookFactories;

    public function __construct(
        CommandHookFactoryInterface ...$commandHookFactories,
    ) {
        $this->commandHookFactories = $commandHookFactories;
    }

    public function build(
        CommandHooksFactoryDependencies $commandHooksFactoryDependencies,
    ): CommandHooks {
        return CommandHooks::fromArray(array_map(
            static fn (CommandHookFactoryInterface $factory) => $factory->build($commandHooksFactoryDependencies),
            $this->commandHookFactories
        ));
    }
}
