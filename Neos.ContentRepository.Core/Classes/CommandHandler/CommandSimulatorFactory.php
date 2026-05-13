<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\DimensionSpaceCommandHandler;
use Neos\ContentRepository\Core\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
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
        private NodeTypeManager $nodeTypeManager,
        private ContentDimensionZookeeper $contentDimensionZookeeper,
        private InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private PropertyConverter $propertyConverter,
    ) {
    }

    public function createSimulatorForWorkspace(WorkspaceName $workspaceNameToSimulateIn): CommandSimulator
    {
        $simulatedContentGraphProjection = $this->contentRepositoryProjection->withSimulation();

        $commandHandlingDependencies = new CommandHandlingDependencies($simulatedContentGraphProjection->getState());

        $commandBusForRebaseableCommands = new CommandBus(
            $commandHandlingDependencies,
            new NodeAggregateCommandHandler(
                $this->nodeTypeManager,
                $this->contentDimensionZookeeper,
                $this->interDimensionalVariationGraph,
                $this->propertyConverter,
            ),
            new DimensionSpaceCommandHandler(
                $this->interDimensionalVariationGraph,
                $this->nodeTypeManager,
            )
        );

        return new CommandSimulator(
            $simulatedContentGraphProjection,
            $this->eventNormalizer,
            $commandBusForRebaseableCommands,
            $workspaceNameToSimulateIn,
        );
    }
}
