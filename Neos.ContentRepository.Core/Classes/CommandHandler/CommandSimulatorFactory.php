<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjectionInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\EventStore\Helper\InMemoryEventStore;

/**
 * @internal
 */
final readonly class CommandSimulatorFactory
{
    /**
     * @param array<CommandHandlerInterface> $handlers
     */
    public function __construct(
        private CommandHandlingDependencies $commandHandlingDependencies,
        private ContentGraphProjectionInterface $contentRepositoryProjection,
        private EventNormalizer $eventNormalizer,
        private array $handlers
    ) {
    }

    public function createSimulator(WorkspaceName $workspaceNameToSimulateIn): CommandSimulator
    {
        return new CommandSimulator(
            $this->commandHandlingDependencies,
            $this->contentRepositoryProjection,
            $this->eventNormalizer,
            $this->handlers,
            new InMemoryEventStore(),
            $workspaceNameToSimulateIn,
        );
    }
}
