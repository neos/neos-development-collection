<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjectionInterface;
use Neos\EventStore\Helper\InMemoryEventStore;

/**
 * @internal
 */
final class CommandSimulatorFactory
{
    /**
     * @param array<CommandHandlerInterface> $handlers
     */
    public function __construct(
        private readonly CommandHandlingDependencies $commandHandlingDependencies,
        private readonly ContentGraphProjectionInterface $contentRepositoryProjection,
        private readonly EventNormalizer $eventNormalizer,
        private readonly array $handlers
    ) {
    }

    public function createSimulator(): CommandSimulator
    {
        return new CommandSimulator(
            $this->commandHandlingDependencies,
            $this->contentRepositoryProjection,
            $this->eventNormalizer,
            $this->handlers,
            new InMemoryEventStore()
        );
    }
}
