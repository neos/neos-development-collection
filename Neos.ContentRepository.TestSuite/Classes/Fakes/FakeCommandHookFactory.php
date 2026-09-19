<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Fakes;

use Neos\ContentRepository\Core\CommandHandler\CommandHookInterface;
use Neos\ContentRepository\Core\Factory\CommandHookFactoryInterface;
use Neos\ContentRepository\Core\Factory\CommandHooksFactoryDependencies;

final class FakeCommandHookFactory implements CommandHookFactoryInterface
{
    private static CommandHookInterface $commandHook;

    public function build(CommandHooksFactoryDependencies $commandHooksFactoryDependencies,): CommandHookInterface
    {
        return static::$commandHook ?? throw new \RuntimeException('No command hook defined for Fake.');
    }

    public static function setCommandHook(CommandHookInterface $commandHook): void
    {
        self::$commandHook = $commandHook;
    }
}
