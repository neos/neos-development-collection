<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\SubtreeTagging;

use Neos\ContentRepository\Core\CommandHandler\CommandHookInterface;
use Neos\ContentRepository\Core\Factory\CommandHookFactoryInterface;
use Neos\ContentRepository\Core\Factory\CommandHooksFactoryDependencies;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
/** @internal */
final readonly class NeosSubtreeTaggingConstraintChecksFactory implements CommandHookFactoryInterface
{
    public function build(CommandHooksFactoryDependencies $commandHooksFactoryDependencies): CommandHookInterface
    {
        return new NeosSubtreeTaggingConstraintChecks($commandHooksFactoryDependencies->contentGraphReadModel);
    }
}
