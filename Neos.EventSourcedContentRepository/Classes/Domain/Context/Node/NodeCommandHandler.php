<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\GraphProjector;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\SetNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\SetNodeReferences;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\RemoveNodesFromAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodePropertiesWereSet;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodeReferencesWereSet;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodesWereRemovedFromAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Exception;
use Neos\EventSourcedContentRepository\Exception\NodeNotFoundException;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\Decorator\EventWithIdentifier;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class NodeCommandHandler
{
    /**
     * @Flow\Inject
     * @var NodeEventPublisher
     */
    protected $nodeEventPublisher;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var InterDimensionalVariationGraph
     */
    protected $interDimensionalVariationGraph;

    /**
     * @Flow\Inject
     * @var ContentDimensionZookeeper
     */
    protected $contentDimensionZookeeper;

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @Flow\Inject
     * @var GraphProjector
     */
    protected $graphProjector;

    /**
     * @Flow\Inject
     * @var ReadSideMemoryCacheManager
     */
    protected $readSideMemoryCacheManager;

    /**
     * @param SetNodeProperties $command
     * @return CommandResult
     */
    public function handleSetNodeProperties(SetNodeProperties $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $events = null;
        $this->nodeEventPublisher->withCommand($command, function () use ($command, &$events) {
            $contentStreamIdentifier = $command->getContentStreamIdentifier();

            // Check if node exists
            // @todo: this must also work when creating a copy on write
            #$this->assertNodeWithOriginDimensionSpacePointExists($contentStreamIdentifier, $command->getNodeAggregateIdentifier(), $command->getOriginDimensionSpacePoint());

            $events = DomainEvents::withSingleEvent(
                EventWithIdentifier::create(
                    new NodePropertiesWereSet(
                        $contentStreamIdentifier,
                        $command->getNodeAggregateIdentifier(),
                        $command->getOriginDimensionSpacePoint(),
                        $command->getPropertyValues()
                    )
                )
            );

            $this->nodeEventPublisher->publishMany(
                ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier)->getEventStreamName(),
                $events
            );
        });
        return CommandResult::fromPublishedEvents($events);
    }

    /**
     * @param SetNodeReferences $command
     * @return CommandResult
     */
    public function handleSetNodeReferences(SetNodeReferences $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $events = null;
        $this->nodeEventPublisher->withCommand($command, function () use ($command, &$events) {
            $events = DomainEvents::withSingleEvent(
                EventWithIdentifier::create(
                    new NodeReferencesWereSet(
                        $command->getContentStreamIdentifier(),
                        $command->getSourceNodeAggregateIdentifier(),
                        $command->getSourceOriginDimensionSpacePoint(),
                        $command->getDestinationNodeAggregateIdentifiers(),
                        $command->getReferenceName()
                    )
                )
            );
            $this->nodeEventPublisher->publishMany(
                ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier())->getEventStreamName(),
                $events
            );
        });

        return CommandResult::fromPublishedEvents($events);
    }

    /**
     * @param RemoveNodesFromAggregate $command
     * @return CommandResult
     * @throws SpecializedDimensionsMustBePartOfDimensionSpacePointSet
     */
    public function handleRemoveNodesFromAggregate(RemoveNodesFromAggregate $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        foreach ($command->getDimensionSpacePointSet()->getPoints() as $point) {
            $specializations = $this->interDimensionalVariationGraph->getSpecializationSet($point, false);
            foreach ($specializations->getPoints() as $specialization) {
                if (!$command->getDimensionSpacePointSet()->contains($specialization)) {
                    throw new SpecializedDimensionsMustBePartOfDimensionSpacePointSet('The parent dimension ' . json_encode($point->getCoordinates()) . ' is in the given DimensionSpacePointSet, but its specialization ' . json_encode($specialization->getCoordinates()) . ' is not. This is currently not supported; and we might need to think through the implications of this case more before allowing it. There is no "technical hard reason" to prevent it; but to me (SK) it feels that it will lead to inconsistent behavior otherwise.',
                        1532154238);
                }
            }
        }

        $events = null;
        $this->nodeEventPublisher->withCommand($command, function () use ($command, &$events) {
            $contentStreamIdentifier = $command->getContentStreamIdentifier();

            // Check if node aggregate exists
            $nodeAggregate = $this->contentGraph->findNodeAggregateByIdentifier($contentStreamIdentifier, $command->getNodeAggregateIdentifier());
            if ($nodeAggregate === null) {
                throw new NodeAggregateNotFound('Node aggregate ' . $command->getNodeAggregateIdentifier() . ' not found', 1532026858);
            }

            $events = DomainEvents::withSingleEvent(
                EventWithIdentifier::create(
                    new NodesWereRemovedFromAggregate(
                        $contentStreamIdentifier,
                        $command->getNodeAggregateIdentifier(),
                        $command->getDimensionSpacePointSet()
                    )
                )
            );

            $this->nodeEventPublisher->publishMany(
                ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier)->getEventStreamName(),
                $events
            );
        });
        return CommandResult::fromPublishedEvents($events);
    }
}
