<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\CommandHandler\CommandHandlerInterface;
use Neos\ContentRepository\Core\CommandHandler\CommandHandlingDependencies;
use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\Exception\DimensionSpacePointIsNoSpecialization;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\DimensionSpace\VariantType;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\Common\WorkspaceConstraintChecks;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\AddDimensionShineThrough;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\MoveDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\DimensionSpacePointsWithAllowedSpecializations;
use Neos\ContentRepository\Core\Feature\Common\DimensionSpacePointWithAllowedSpecializations;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionShineThroughWasAdded;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Exception\DimensionSpacePointAlreadyExists;
use Neos\ContentRepository\Core\Feature\RebaseableCommand;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal from userland, you'll use ContentRepository::handle to dispatch commands
 */
final readonly class DimensionSpaceCommandHandler implements CommandHandlerInterface
{
    use ConstraintChecks;
    use WorkspaceConstraintChecks;

    public function __construct(
        private InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private NodeTypeManager $nodeTypeManager,
    ) {
    }

    public function canHandle(CommandInterface|RebasableToOtherWorkspaceInterface $command): bool
    {
        return method_exists($this, 'handle' . (new \ReflectionClass($command))->getShortName());
    }

    public function handle(CommandInterface|RebasableToOtherWorkspaceInterface $command, CommandHandlingDependencies $commandHandlingDependencies): EventsToPublish
    {
        /** @phpstan-ignore-next-line */
        return match ($command::class) {
            MoveDimensionSpacePoint::class => $this->handleMoveDimensionSpacePoint($command, $commandHandlingDependencies),
            AddDimensionShineThrough::class => $this->handleAddDimensionShineThrough($command, $commandHandlingDependencies),
        };
    }

    private function handleMoveDimensionSpacePoint(
        MoveDimensionSpacePoint $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $expectedVersion = ExpectedVersion::fromVersion($commandHandlingDependencies->getContentStreamVersion($contentGraph->getContentStreamId()));
        $streamName = ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId())
            ->getEventStreamName();

        $this->requireDimensionSpacePointToExist($command->target);
        $this->requireWorkspaceToBeRootOrRootBasedForDimensionAdjustment($command->workspaceName, $commandHandlingDependencies);
        $relevantWorkspaces = $commandHandlingDependencies->findAllWorkspaces()->filter(
            fn (Workspace $workspace): bool => $workspace->isRootWorkspace()
                || !$workspace->workspaceName->equals($command->initialWorkspaceName)
        );
        foreach ($relevantWorkspaces as $workspace) {
            self::requireDimensionSpacePointToBeEmptyInContentStream(
                $commandHandlingDependencies->getContentGraph($workspace->workspaceName),
                $command->target,
            );
        }
        self::requireNoWorkspaceToHaveChanges($relevantWorkspaces);
        $fallbackConstraints = DimensionSpacePointsWithAllowedSpecializations::create(
            DimensionSpacePointWithAllowedSpecializations::create(
                $command->source,
                $this->interDimensionalVariationGraph->getSpecializationSet($command->target, false),
            )
        );
        foreach ($contentGraph->findRootNodeAggregates(FindRootNodeAggregatesFilter::create()) as $rootNodeAggregate) {
            $this->requireDescendantNodesToNotFallbackToDimensionSpacePointsOtherThan(
                $rootNodeAggregate->nodeAggregateId,
                $contentGraph,
                $fallbackConstraints,
            );
        }

        return new EventsToPublish(
            $streamName,
            RebaseableCommand::enrichWithCommand(
                $command,
                Events::with(
                    new DimensionSpacePointWasMoved(
                        $contentGraph->getWorkspaceName(),
                        $contentGraph->getContentStreamId(),
                        $command->source,
                        $command->target
                    ),
                )
            ),
            $expectedVersion
        );
    }

    private function handleAddDimensionShineThrough(
        AddDimensionShineThrough $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $expectedVersion = ExpectedVersion::fromVersion($commandHandlingDependencies->getContentStreamVersion($contentGraph->getContentStreamId()));
        $streamName = ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId())
            ->getEventStreamName();

        $this->requireDimensionSpacePointToExist($command->target);
        $this->requireDimensionSpacePointToBeSpecialization($command->target, $command->source);
        $this->requireWorkspaceToBeRootOrRootBasedForDimensionAdjustment($command->workspaceName, $commandHandlingDependencies);
        $relevantWorkspaces = $commandHandlingDependencies->findAllWorkspaces()->filter(
            fn (Workspace $workspace): bool => $workspace->isRootWorkspace()
                || !$workspace->workspaceName->equals($command->initialWorkspaceName)
        );
        foreach ($relevantWorkspaces as $workspace) {
            self::requireDimensionSpacePointToBeEmptyInContentStream(
                $commandHandlingDependencies->getContentGraph($workspace->workspaceName),
                $command->target,
            );
        }

        return new EventsToPublish(
            $streamName,
            RebaseableCommand::enrichWithCommand(
                $command,
                Events::with(
                    new DimensionShineThroughWasAdded(
                        $contentGraph->getWorkspaceName(),
                        $contentGraph->getContentStreamId(),
                        $command->source,
                        $command->target
                    )
                )
            ),
            $expectedVersion
        );
    }

    private function requireDimensionSpacePointToBeSpecialization(
        DimensionSpacePoint $target,
        DimensionSpacePoint $source
    ): void {
        if (
            $this->interDimensionalVariationGraph->getVariantType(
                $target,
                $source
            ) !== VariantType::TYPE_SPECIALIZATION
        ) {
            throw DimensionSpacePointIsNoSpecialization::butWasSupposedToBe($target, $source);
        }
    }

    protected function getNodeTypeManager(): NodeTypeManager
    {
        return $this->nodeTypeManager;
    }

    protected function getAllowedDimensionSubspace(): DimensionSpacePointSet
    {
        return $this->interDimensionalVariationGraph->getDimensionSpacePoints();
    }
}
