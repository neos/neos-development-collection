<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjectionInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @internal
 */
final readonly class CommandSimulatorFactory
{
    public function __construct(
        private ContentGraphProjectionInterface $contentRepositoryProjection,
        private EventNormalizer $eventNormalizer,
        private CommandBus $commandBus
    ) {
    }

    public function createSimulatorForWorkspace(WorkspaceName $workspaceNameToSimulateIn): CommandSimulator
    {
        return new CommandSimulator(
            $this->contentRepositoryProjection,
            $this->eventNormalizer,
            $this->commandBus,
            $workspaceNameToSimulateIn,
        );
    }
}
