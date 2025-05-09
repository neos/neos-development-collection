<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Factory;

use Neos\ContentRepository\Core\CommandHandler\CommandHookInterface;

/**
 * @api for implementers of custom {@see CommandHookInterface}s
 */
interface CommandHookFactoryInterface
{
    public function build(
        CommandHooksFactoryDependencies $commandHooksFactoryDependencies,
    ): CommandHookInterface;
}
