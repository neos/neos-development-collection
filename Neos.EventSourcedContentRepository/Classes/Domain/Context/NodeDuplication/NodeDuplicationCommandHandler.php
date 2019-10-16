<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication;

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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamRepository;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\ConstraintChecks;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateEventPublisher;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\Decorator\EventWithIdentifier;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\Command\CopyNodesRecursively;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;

final class NodeDuplicationCommandHandler
{

    use ConstraintChecks;

    /**
     * @var NodeAggregateCommandHandler
     */
    protected $nodeAggregateCommandHandler;

    /**
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @var ContentStreamRepository
     */
    protected $contentStreamRepository;

    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var ReadSideMemoryCacheManager
     */
    protected $readSideMemoryCacheManager;

    /**
     * @var NodeAggregateEventPublisher
     */
    protected $nodeAggregateEventPublisher;

    /**
     * @var DimensionSpacePointSet
     */
    protected $allowedDimensionSubspace;

    /**
     * @var InterDimensionalVariationGraph
     */
    protected $interDimensionalVariationGraph;

    /**
     * NodeDuplicationCommandHandler constructor.
     * @param NodeAggregateCommandHandler $nodeAggregateCommandHandler
     * @param ContentGraphInterface $contentGraph
     * @param ContentStreamRepository $contentStreamRepository
     * @param NodeTypeManager $nodeTypeManager
     * @param ReadSideMemoryCacheManager $readSideMemoryCacheManager
     * @param NodeAggregateEventPublisher $nodeAggregateEventPublisher
     * @param ContentDimensionZookeeper $contentDimensionZookeeper
     * @param InterDimensionalVariationGraph $interDimensionalVariationGraph
     */
    public function __construct(NodeAggregateCommandHandler $nodeAggregateCommandHandler, ContentGraphInterface $contentGraph, ContentStreamRepository $contentStreamRepository, NodeTypeManager $nodeTypeManager, ReadSideMemoryCacheManager $readSideMemoryCacheManager, NodeAggregateEventPublisher $nodeAggregateEventPublisher, ContentDimensionZookeeper $contentDimensionZookeeper, InterDimensionalVariationGraph $interDimensionalVariationGraph)
    {
        $this->nodeAggregateCommandHandler = $nodeAggregateCommandHandler;
        $this->contentGraph = $contentGraph;
        $this->contentStreamRepository = $contentStreamRepository;
        $this->nodeTypeManager = $nodeTypeManager;
        $this->readSideMemoryCacheManager = $readSideMemoryCacheManager;
        $this->nodeAggregateEventPublisher = $nodeAggregateEventPublisher;
        $this->allowedDimensionSubspace = $contentDimensionZookeeper->getAllowedDimensionSubspace();
        $this->interDimensionalVariationGraph = $interDimensionalVariationGraph;
    }


    protected function getContentGraph(): ContentGraphInterface
    {
        return $this->contentGraph;
    }

    protected function getContentStreamRepository(): ContentStreamRepository
    {
        return $this->contentStreamRepository;
    }

    protected function getNodeTypeManager(): NodeTypeManager
    {
        return $this->nodeTypeManager;
    }

    protected function getAllowedDimensionSubspace(): DimensionSpacePointSet
    {
        return $this->allowedDimensionSubspace;
    }

    /**
     * @param CopyNodesRecursively $command
     * @throws \Neos\ContentRepository\Exception\NodeConstraintException
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     * @throws \Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamDoesNotExistYet
     */
    public function handleCopyNodesRecursively(CopyNodesRecursively $command)
    {
        $this->readSideMemoryCacheManager->disableCache();

        // Basic constraints (Content Stream / Dimension Space Point / Node Type of to-be-inserted root node)
        $this->requireContentStreamToExist($command->getContentStreamIdentifier());
        $this->requireDimensionSpacePointToExist($command->getDimensionSpacePoint());
        $nodeType = $this->requireNodeType($command->getNodeToInsert()->getNodeTypeName());
        $this->requireNodeTypeToNotBeOfTypeRoot($nodeType);

        // Constraint: Does the target parent node allow nodes of this type?
        // NOTE: we only check this for the *root* node of the to-be-inserted structure; and not for its
        // children (as we want to create the structure as-is; assuming it was already valid beforehand).
        $this->requireConstraintsImposedByAncestorsAreMet($command->getContentStreamIdentifier(), $nodeType, $command->getNodeToInsert()->getNodeName(), [$command->getTargetParentNodeAggregateIdentifier()]);

        // Constraint: The new nodeAggregateIdentifiers are not allowed to exist yet.
        $this->requireNestedNodeAggregatesToNotExist($command->getContentStreamIdentifier(), $command->getNodeToInsert());

        // Constraint: the parent node must exist in the command's DimensionSpacePoint as well
        $parentNodeAggregate = $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $command->getTargetParentNodeAggregateIdentifier());
        if ($command->getTargetSucceedingSiblingNodeAggregateIdentifier()) {
            $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $command->getTargetSucceedingSiblingNodeAggregateIdentifier());
        }
        $this->requireNodeAggregateToCoverDimensionSpacePoint($parentNodeAggregate, $command->getDimensionSpacePoint());

        // Calculate Covered Dimension Space Points: All points being specializations of the
        // given DSP, where the parent also exists.
        $specializations = $this->interDimensionalVariationGraph->getSpecializationSet($command->getDimensionSpacePoint());
        $coveredDimensionSpacePoints = $specializations->getIntersection($parentNodeAggregate->getCoveredDimensionSpacePoints());

        // Constraint: The node name must be free in all these dimension space points
        if ($command->getNodeToInsert()->getNodeName()) {
            $this->requireNodeNameToBeUnoccupied(
                $command->getContentStreamIdentifier(),
                $command->getNodeToInsert()->getNodeName(),
                $command->getTargetParentNodeAggregateIdentifier(),
                $command->getDimensionSpacePoint(),
                $coveredDimensionSpacePoints
            );
        }

        // Now, we can start creating the recursive structure.
        $events = DomainEvents::createEmpty();
        $this->nodeAggregateEventPublisher->withCommand($command, function () use ($command, $nodeType, $parentNodeAggregate, $coveredDimensionSpacePoints, &$events) {
            $this->createEventsForNodeToInsert(
                $command->getContentStreamIdentifier(),
                $command->getDimensionSpacePoint(),
                $coveredDimensionSpacePoints,
                $command->getTargetParentNodeAggregateIdentifier(),
                $command->getTargetSucceedingSiblingNodeAggregateIdentifier(),
                $command->getNodeToInsert(),
                $events
            );

            $contentStreamEventStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier());
            $this->nodeAggregateEventPublisher->publishMany(
                $contentStreamEventStreamName->getEventStreamName(),
                $events
            );
        });

        return CommandResult::fromPublishedEvents($events);
    }

    private function requireNestedNodeAggregatesToNotExist(\Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier $contentStreamIdentifier, Command\Dto\NodeToInsert $nodeToInsert)
    {
        $this->requireProjectedNodeAggregateToNotExist($contentStreamIdentifier, $nodeToInsert->getNodeAggregateIdentifier());

        foreach ($nodeToInsert->getChildNodesToInsert() as $childNodeToInsert) {
            $this->requireNestedNodeAggregatesToNotExist($contentStreamIdentifier, $childNodeToInsert);
        }
    }

    private function createEventsForNodeToInsert(\Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier $contentStreamIdentifier, \Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint $dimensionSpacePoint, DimensionSpacePointSet $coveredDimensionSpacePoints, NodeAggregateIdentifier $targetParentNodeAggregateIdentifier, ?NodeAggregateIdentifier $targetSucceedingSiblingNodeAggregateIdentifier, Command\Dto\NodeToInsert $nodeToInsert, \Neos\EventSourcing\Event\DomainEvents &$events)
    {
        $events = $events->appendEvent(EventWithIdentifier::create(
            new NodeAggregateWithNodeWasCreated(
                $contentStreamIdentifier,
                $nodeToInsert->getNodeAggregateIdentifier(),
                $nodeToInsert->getNodeTypeName(),
                $dimensionSpacePoint,
                $coveredDimensionSpacePoints,
                $targetParentNodeAggregateIdentifier,
                $nodeToInsert->getNodeName(),
                $nodeToInsert->getPropertyValues(),
                $nodeToInsert->getNodeAggregateClassification(),
                $targetSucceedingSiblingNodeAggregateIdentifier
            )
        ));

        foreach ($nodeToInsert->getChildNodesToInsert() as $childNodeToInsert) {
            $this->createEventsForNodeToInsert(
                $contentStreamIdentifier,
                $dimensionSpacePoint,
                $coveredDimensionSpacePoints,
                $nodeToInsert->getNodeAggregateIdentifier(), // the just-inserted node becomes the new parent node Identifier
                null, // $childNodesToInsert is already in the correct order; so appending only is fine.
                $childNodeToInsert,
                $events
            );
        }
    }

}
