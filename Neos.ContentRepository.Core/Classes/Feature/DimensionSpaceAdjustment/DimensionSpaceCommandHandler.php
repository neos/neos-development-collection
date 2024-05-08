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
use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\Exception\DimensionSpacePointIsNoSpecialization;
use Neos\ContentRepository\Core\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\DimensionSpace\VariantType;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\AddDimensionShineThrough;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\MoveDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionShineThroughWasAdded;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Exception\DimensionSpacePointAlreadyExists;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal from userland, you'll use ContentRepository::handle to dispatch commands
 */
final readonly class DimensionSpaceCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private ContentDimensionZookeeper $contentDimensionZookeeper,
        private InterDimensionalVariationGraph $interDimensionalVariationGraph,
    ) {
    }

    public function canHandle(CommandInterface $command): bool
    {
        return method_exists($this, 'handle' . (new \ReflectionClass($command))->getShortName());
    }

    public function handle(CommandInterface $command, CommandHandlingDependencies $commandHandlingDependencies): EventsToPublish
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
        $streamName = ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId())
            ->getEventStreamName();

        self::requireDimensionSpacePointToBeEmptyInContentStream(
            $contentGraph,
            $command->target
        );
        $this->requireDimensionSpacePointToExist($command->target);

        return new EventsToPublish(
            $streamName,
            Events::with(
                new DimensionSpacePointWasMoved(
                    $contentGraph->getContentStreamId(),
                    $command->source,
                    $command->target
                ),
            ),
            ExpectedVersion::ANY()
        );
    }

    private function handleAddDimensionShineThrough(
        AddDimensionShineThrough $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $streamName = ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId())
            ->getEventStreamName();

        self::requireDimensionSpacePointToBeEmptyInContentStream(
            $contentGraph,
            $command->target
        );
        $this->requireDimensionSpacePointToExist($command->target);

        $this->requireDimensionSpacePointToBeSpecialization($command->target, $command->source);

        return new EventsToPublish(
            $streamName,
            Events::with(
                new DimensionShineThroughWasAdded(
                    $contentGraph->getContentStreamId(),
                    $command->source,
                    $command->target
                )
            ),
            ExpectedVersion::ANY()
        );
    }

    /**
     * @throws DimensionSpacePointNotFound
     */
    protected function requireDimensionSpacePointToExist(DimensionSpacePoint $dimensionSpacePoint): void
    {
        $allowedDimensionSubspace = $this->contentDimensionZookeeper->getAllowedDimensionSubspace();
        if (!$allowedDimensionSubspace->contains($dimensionSpacePoint)) {
            throw DimensionSpacePointNotFound::becauseItIsNotWithinTheAllowedDimensionSubspace($dimensionSpacePoint);
        }
    }

    private static function requireDimensionSpacePointToBeEmptyInContentStream(
        ContentGraphInterface $contentGraph,
        DimensionSpacePoint $dimensionSpacePoint
    ): void {
        $hasNodes = $contentGraph->getSubgraph($dimensionSpacePoint, VisibilityConstraints::withoutRestrictions())->countNodes();
        if ($hasNodes > 0) {
            throw new DimensionSpacePointAlreadyExists(sprintf(
                'the content stream %s already contained nodes in dimension space point %s - this is not allowed.',
                $contentGraph->getContentStreamId()->value,
                $dimensionSpacePoint->toJson(),
            ), 1612898126);
        }
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
}
