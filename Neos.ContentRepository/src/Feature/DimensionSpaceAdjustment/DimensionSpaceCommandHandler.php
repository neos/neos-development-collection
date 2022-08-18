<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\DimensionSpaceAdjustment;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\CommandHandler\CommandHandlerInterface;
use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointIsNoSpecialization;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\VariantType;
use Neos\ContentRepository\EventStore\Events;
use Neos\ContentRepository\EventStore\EventsToPublish;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Command\AddDimensionShineThrough;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Command\MoveDimensionSpacePoint;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Event\DimensionShineThroughWasAdded;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Exception\DimensionSpacePointAlreadyExists;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\ContentGraph\ContentGraphInterface;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * ContentStreamCommandHandler
 */
final class DimensionSpaceCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly ContentDimensionZookeeper $contentDimensionZookeeper,
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
    ) {
    }

    public function canHandle(CommandInterface $command): bool
    {
        return $command instanceof MoveDimensionSpacePoint
            || $command instanceof AddDimensionShineThrough;
    }

    public function handle(CommandInterface $command, ContentRepository $contentRepository): EventsToPublish
    {
        if ($command instanceof MoveDimensionSpacePoint) {
            return $this->handleMoveDimensionSpacePoint($command, $contentRepository);
        } elseif ($command instanceof AddDimensionShineThrough) {
            return $this->handleAddDimensionShineThrough($command, $contentRepository);
        }

        throw new \RuntimeException('Command ' . get_class($command) . ' not supported by this command handler.');
    }

    private function handleMoveDimensionSpacePoint(
        MoveDimensionSpacePoint $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($command->contentStreamIdentifier)
            ->getEventStreamName();

        self::requireDimensionSpacePointToBeEmptyInContentStream(
            $command->target,
            $command->contentStreamIdentifier,
            $contentRepository->getContentGraph()
        );
        $this->requireDimensionSpacePointToExistInConfiguration($command->target);

        return new EventsToPublish(
            $streamName,
            Events::with(
                new DimensionSpacePointWasMoved(
                    $command->contentStreamIdentifier,
                    $command->source,
                    $command->target
                ),
            ),
            ExpectedVersion::ANY()
        );
    }

    private function handleAddDimensionShineThrough(
        AddDimensionShineThrough $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($command->contentStreamIdentifier)
            ->getEventStreamName();

        self::requireDimensionSpacePointToBeEmptyInContentStream(
            $command->target,
            $command->contentStreamIdentifier,
            $contentRepository->getContentGraph()
        );
        $this->requireDimensionSpacePointToExistInConfiguration($command->target);

        $this->requireDimensionSpacePointToBeSpecialization($command->target, $command->source);

        return new EventsToPublish(
            $streamName,
            Events::with(
                new DimensionShineThroughWasAdded(
                    $command->contentStreamIdentifier,
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
    protected function requireDimensionSpacePointToExistInConfiguration(DimensionSpacePoint $dimensionSpacePoint): void
    {
        $allowedDimensionSubspace = $this->contentDimensionZookeeper->getAllowedDimensionSubspace();
        if (!$allowedDimensionSubspace->contains($dimensionSpacePoint)) {
            throw DimensionSpacePointNotFound::becauseItIsNotWithinTheAllowedDimensionSubspace($dimensionSpacePoint);
        }
    }

    private static function requireDimensionSpacePointToBeEmptyInContentStream(
        DimensionSpacePoint $dimensionSpacePoint,
        ContentStreamIdentifier $contentStreamIdentifier,
        ContentGraphInterface $contentGraph
    ): void {
        $subgraph = $contentGraph->getSubgraph(
            $contentStreamIdentifier,
            $dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        if ($subgraph->countNodes() > 0) {
            throw new DimensionSpacePointAlreadyExists(sprintf(
                'the content stream %s already contained nodes in dimension space point %s - this is not allowed.',
                $contentStreamIdentifier,
                $dimensionSpacePoint
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
