<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\DimensionSpace;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\VariantType;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\CommandResult;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\DimensionSpace\Exception\DimensionSpacePointAlreadyExists;
use Neos\EventSourcedContentRepository\Domain\Context\DimensionSpace\Exception\DimensionSpacePointIsNoSpecialization;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventStore;
use Ramsey\Uuid\Uuid;

/**
 * @Flow\Scope("singleton")
 * ContentStreamCommandHandler
 */
final class DimensionSpaceCommandHandler
{

    /**
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @var ReadSideMemoryCacheManager
     */
    protected $readSideMemoryCacheManager;

    /**
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    protected ContentDimensionZookeeper $contentDimensionZookeeper;

    protected DimensionSpacePointSet $allowedDimensionSubspace;

    protected InterDimensionalVariationGraph $interDimensionalVariationGraph;

    protected RuntimeBlocker $runtimeBlocker;

    public function __construct(EventStore $eventStore, ReadSideMemoryCacheManager $readSideMemoryCacheManager, ContentGraphInterface $contentGraph, ContentDimensionZookeeper $contentDimensionZookeeper, InterDimensionalVariationGraph $interDimensionalVariationGraph, RuntimeBlocker $runtimeBlocker)
    {
        $this->eventStore = $eventStore;
        $this->readSideMemoryCacheManager = $readSideMemoryCacheManager;
        $this->contentGraph = $contentGraph;
        $this->contentDimensionZookeeper = $contentDimensionZookeeper;
        $this->interDimensionalVariationGraph = $interDimensionalVariationGraph;
        $this->runtimeBlocker = $runtimeBlocker;
    }


    /**
     * @param Command\MoveDimensionSpacePoint $command
     * @return CommandResult
     */
    public function handleMoveDimensionSpacePoint(Command\MoveDimensionSpacePoint $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier())->getEventStreamName();

        $this->requireDimensionSpacePointToBeEmptyInContentStream($command->getTarget(), $command->getContentStreamIdentifier());
        $this->requireDimensionSpacePointToExistInConfiguration($command->getTarget());

        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new Event\DimensionSpacePointWasMoved(
                    $command->getContentStreamIdentifier(),
                    $command->getSource(),
                    $command->getTarget()
                ),
                Uuid::uuid4()->toString()
            )
        );
        $this->eventStore->commit($streamName, $events);
        return CommandResult::fromPublishedEvents($events, $this->runtimeBlocker);
    }

    public function handleAddDimensionShineThrough(Command\AddDimensionShineThrough $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier())->getEventStreamName();

        $this->requireDimensionSpacePointToBeEmptyInContentStream($command->getTarget(), $command->getContentStreamIdentifier());
        $this->requireDimensionSpacePointToExistInConfiguration($command->getTarget());

        $this->requireDimensionSpacePointToBeSpecialization($command->getTarget(), $command->getSource());

        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new Event\DimensionShineThroughWasAdded(
                    $command->getContentStreamIdentifier(),
                    $command->getSource(),
                    $command->getTarget()
                ),
                Uuid::uuid4()->toString()
            )
        );
        $this->eventStore->commit($streamName, $events);
        return CommandResult::fromPublishedEvents($events, $this->runtimeBlocker);
    }

    /**
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @throws DimensionSpacePointNotFound
     */
    protected function requireDimensionSpacePointToExistInConfiguration(DimensionSpacePoint $dimensionSpacePoint): void
    {
        $allowedDimensionSubspace = $this->contentDimensionZookeeper->getAllowedDimensionSubspace();
        if (!$allowedDimensionSubspace->contains($dimensionSpacePoint)) {
            throw new DimensionSpacePointNotFound(sprintf('%s was not found in the allowed dimension subspace', $dimensionSpacePoint), 1520260137);
        }
    }


    protected function requireDimensionSpacePointToBeEmptyInContentStream(DimensionSpacePoint $dimensionSpacePoint, ContentStreamIdentifier $contentStreamIdentifier): void
    {
        $subgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier, $dimensionSpacePoint, VisibilityConstraints::withoutRestrictions());
        if ($subgraph->countNodes() > 0) {
            throw new DimensionSpacePointAlreadyExists(sprintf('the content stream %s already contained nodes in dimension space point %s - this is not allowed.', $contentStreamIdentifier, $dimensionSpacePoint), 1612898126);
        }
    }

    private function requireDimensionSpacePointToBeSpecialization(DimensionSpacePoint $target, DimensionSpacePoint $source)
    {
        if ($this->interDimensionalVariationGraph->getVariantType($target, $source) !== VariantType::TYPE_SPECIALIZATION) {
            throw new DimensionSpacePointIsNoSpecialization(sprintf('The Dimension space point %s is no specialization of %s.', $target, $source), 1617275140);
        }
    }
}
