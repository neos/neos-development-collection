<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\NodeDuplication;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Feature\Common\NodeConstraintException;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\ContentStreamRepository;
use Neos\ContentRepository\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\Feature\NodeDuplication\Command\NodeAggregateIdentifierMapping;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\NodeDuplication\Command\CopyNodesRecursively;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Ramsey\Uuid\Uuid;

final class NodeDuplicationCommandHandler
{
    use ConstraintChecks;

    protected NodeAggregateCommandHandler $nodeAggregateCommandHandler;

    protected ContentGraphInterface $contentGraph;

    protected ContentStreamRepository $contentStreamRepository;

    protected NodeTypeManager $nodeTypeManager;

    protected ReadSideMemoryCacheManager $readSideMemoryCacheManager;

    protected NodeAggregateEventPublisher $nodeAggregateEventPublisher;

    protected InterDimensionalVariationGraph $interDimensionalVariationGraph;

    protected RuntimeBlocker $runtimeBlocker;

    private ContentDimensionZookeeper $contentDimensionZookeeper;

    public function __construct(
        NodeAggregateCommandHandler $nodeAggregateCommandHandler,
        ContentGraphInterface $contentGraph,
        ContentStreamRepository $contentStreamRepository,
        NodeTypeManager $nodeTypeManager,
        ReadSideMemoryCacheManager $readSideMemoryCacheManager,
        NodeAggregateEventPublisher $nodeAggregateEventPublisher,
        ContentDimensionZookeeper $contentDimensionZookeeper,
        InterDimensionalVariationGraph $interDimensionalVariationGraph,
        RuntimeBlocker $runtimeBlocker
    ) {
        $this->nodeAggregateCommandHandler = $nodeAggregateCommandHandler;
        $this->contentGraph = $contentGraph;
        $this->contentStreamRepository = $contentStreamRepository;
        $this->nodeTypeManager = $nodeTypeManager;
        $this->readSideMemoryCacheManager = $readSideMemoryCacheManager;
        $this->nodeAggregateEventPublisher = $nodeAggregateEventPublisher;
        $this->contentDimensionZookeeper = $contentDimensionZookeeper;
        $this->interDimensionalVariationGraph = $interDimensionalVariationGraph;
        $this->runtimeBlocker = $runtimeBlocker;
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
        return $this->contentDimensionZookeeper->getAllowedDimensionSubspace();
    }

    /**
     * @throws NodeConstraintException
     */
    public function handleCopyNodesRecursively(CopyNodesRecursively $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        // Basic constraints (Content Stream / Dimension Space Point / Node Type of to-be-inserted root node)
        $this->requireContentStreamToExist($command->getContentStreamIdentifier());
        $this->requireDimensionSpacePointToExist(
            $command->getTargetDimensionSpacePoint()->toDimensionSpacePoint()
        );
        $nodeType = $this->requireNodeType($command->getNodeToInsert()->getNodeTypeName());
        $this->requireNodeTypeToNotBeOfTypeRoot($nodeType);

        // Constraint: Does the target parent node allow nodes of this type?
        // NOTE: we only check this for the *root* node of the to-be-inserted structure; and not for its
        // children (as we want to create the structure as-is; assuming it was already valid beforehand).
        $this->requireConstraintsImposedByAncestorsAreMet(
            $command->getContentStreamIdentifier(),
            $nodeType,
            $command->getTargetNodeName(),
            [$command->getTargetParentNodeAggregateIdentifier()]
        );

        // Constraint: The new nodeAggregateIdentifiers are not allowed to exist yet.
        $this->requireNewNodeAggregateIdentifiersToNotExist(
            $command->getContentStreamIdentifier(),
            $command->getNodeAggregateIdentifierMapping()
        );

        // Constraint: the parent node must exist in the command's DimensionSpacePoint as well
        $parentNodeAggregate = $this->requireProjectedNodeAggregate(
            $command->getContentStreamIdentifier(),
            $command->getTargetParentNodeAggregateIdentifier()
        );
        if ($command->getTargetSucceedingSiblingNodeAggregateIdentifier()) {
            $this->requireProjectedNodeAggregate(
                $command->getContentStreamIdentifier(),
                $command->getTargetSucceedingSiblingNodeAggregateIdentifier()
            );
        }
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $parentNodeAggregate,
            $command->getTargetDimensionSpacePoint()->toDimensionSpacePoint()
        );

        // Calculate Covered Dimension Space Points: All points being specializations of the
        // given DSP, where the parent also exists.
        $specializations = $this->interDimensionalVariationGraph->getSpecializationSet(
            $command->getTargetDimensionSpacePoint()->toDimensionSpacePoint()
        );
        $coveredDimensionSpacePoints = $specializations->getIntersection(
            $parentNodeAggregate->getCoveredDimensionSpacePoints()
        );

        // Constraint: The node name must be free in all these dimension space points
        if ($command->getTargetNodeName()) {
            $this->requireNodeNameToBeUnoccupied(
                $command->getContentStreamIdentifier(),
                $command->getTargetNodeName(),
                $command->getTargetParentNodeAggregateIdentifier(),
                $command->getTargetDimensionSpacePoint(),
                $coveredDimensionSpacePoints
            );
        }

        // Now, we can start creating the recursive structure.
        $events = DomainEvents::createEmpty();
        $this->nodeAggregateEventPublisher->withCommand(
            $command,
            function () use ($command, $coveredDimensionSpacePoints, &$events) {
                $this->createEventsForNodeToInsert(
                    $command->getContentStreamIdentifier(),
                    $command->getTargetDimensionSpacePoint(),
                    $coveredDimensionSpacePoints,
                    $command->getTargetParentNodeAggregateIdentifier(),
                    $command->getTargetSucceedingSiblingNodeAggregateIdentifier(),
                    $command->getTargetNodeName(),
                    $command->getNodeToInsert(),
                    $command->getNodeAggregateIdentifierMapping(),
                    $command->getInitiatingUserIdentifier(),
                    $events
                );

                $contentStreamEventStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
                    $command->getContentStreamIdentifier()
                );
                $this->nodeAggregateEventPublisher->enrichWithCommand(
                    $contentStreamEventStreamName->getEventStreamName(),
                    $events
                );
            }
        );

        return CommandResult::fromPublishedEvents($events, $this->runtimeBlocker);
    }

    private function requireNewNodeAggregateIdentifiersToNotExist(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifierMapping $nodeAggregateIdentifierMapping
    ): void {
        foreach ($nodeAggregateIdentifierMapping->getAllNewNodeAggregateIdentifiers() as $nodeAggregateIdentifier) {
            $this->requireProjectedNodeAggregateToNotExist($contentStreamIdentifier, $nodeAggregateIdentifier);
        }
    }

    private function createEventsForNodeToInsert(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        NodeAggregateIdentifier $targetParentNodeAggregateIdentifier,
        ?NodeAggregateIdentifier $targetSucceedingSiblingNodeAggregateIdentifier,
        ?NodeName $targetNodeName,
        \Neos\ContentRepository\Feature\NodeDuplication\Command\NodeSubtreeSnapshot $nodeToInsert,
        NodeAggregateIdentifierMapping $nodeAggregateIdentifierMapping,
        UserIdentifier $initiatingUserIdentifier,
        DomainEvents &$events
    ): void {
        $events = $events->appendEvent(
            DecoratedEvent::addIdentifier(
                new NodeAggregateWithNodeWasCreated(
                    $contentStreamIdentifier,
                    $nodeAggregateIdentifierMapping->getNewNodeAggregateIdentifier(
                        $nodeToInsert->getNodeAggregateIdentifier()
                    ) ?: NodeAggregateIdentifier::create(),
                    $nodeToInsert->getNodeTypeName(),
                    $originDimensionSpacePoint,
                    $coveredDimensionSpacePoints,
                    $targetParentNodeAggregateIdentifier,
                    $targetNodeName,
                    $nodeToInsert->getPropertyValues(),
                    $nodeToInsert->getNodeAggregateClassification(),
                    $initiatingUserIdentifier,
                    $targetSucceedingSiblingNodeAggregateIdentifier
                ),
                Uuid::uuid4()->toString()
            )
        );

        foreach ($nodeToInsert->getChildNodesToInsert() as $childNodeToInsert) {
            $this->createEventsForNodeToInsert(
                $contentStreamIdentifier,
                $originDimensionSpacePoint,
                $coveredDimensionSpacePoints,
                // the just-inserted node becomes the new parent node Identifier
                $nodeAggregateIdentifierMapping->getNewNodeAggregateIdentifier(
                    $nodeToInsert->getNodeAggregateIdentifier()
                ) ?: NodeAggregateIdentifier::create(),
                // $childNodesToInsert is already in the correct order; so appending only is fine.
                null,
                $childNodeToInsert->getNodeName(),
                $childNodeToInsert,
                $nodeAggregateIdentifierMapping,
                $initiatingUserIdentifier,
                $events
            );
        }
    }
}
