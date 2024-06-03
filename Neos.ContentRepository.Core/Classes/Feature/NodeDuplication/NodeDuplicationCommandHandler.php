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

namespace Neos\ContentRepository\Core\Feature\NodeDuplication;

use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\CommandHandler\CommandHandlerInterface;
use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\Common\NodeCreationInternals;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Command\CopyNodesRecursively;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Dto\NodeSubtreeSnapshot;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeConstraintException;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @internal from userland, you'll use ContentRepository::handle to dispatch commands
 */
final class NodeDuplicationCommandHandler implements CommandHandlerInterface
{
    use ConstraintChecks;
    use NodeCreationInternals;

    public function __construct(
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly ContentDimensionZookeeper $contentDimensionZookeeper,
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
    ) {
    }

    protected function getNodeTypeManager(): NodeTypeManager
    {
        return $this->nodeTypeManager;
    }

    protected function getAllowedDimensionSubspace(): DimensionSpacePointSet
    {
        return $this->contentDimensionZookeeper->getAllowedDimensionSubspace();
    }

    public function canHandle(CommandInterface $command): bool
    {
        return method_exists($this, 'handle' . (new \ReflectionClass($command))->getShortName());
    }

    public function handle(CommandInterface $command, CommandHandlingDependencies $commandHandlingDependencies): EventsToPublish
    {
        /** @phpstan-ignore-next-line */
        return match ($command::class) {
            CopyNodesRecursively::class => $this->handleCopyNodesRecursively($command, $commandHandlingDependencies),
        };
    }

    /**
     * @throws NodeConstraintException
     */
    private function handleCopyNodesRecursively(
        CopyNodesRecursively $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        // Basic constraints (Content Stream / Dimension Space Point / Node Type of to-be-inserted root node)
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $expectedVersion = $this->getExpectedVersionOfContentStream($contentGraph->getContentStreamId(), $commandHandlingDependencies);
        $this->requireDimensionSpacePointToExist(
            $command->targetDimensionSpacePoint->toDimensionSpacePoint()
        );
        $nodeType = $this->requireNodeType($command->nodeTreeToInsert->nodeTypeName);
        $this->requireNodeTypeToNotBeOfTypeRoot($nodeType);

        // Constraint: Does the target parent node allow nodes of this type?
        // NOTE: we only check this for the *root* node of the to-be-inserted structure; and not for its
        // children (as we want to create the structure as-is; assuming it was already valid beforehand).
        $this->requireConstraintsImposedByAncestorsAreMet(
            $contentGraph,
            $nodeType,
            [$command->targetParentNodeAggregateId]
        );

        // Constraint: The new nodeAggregateIds are not allowed to exist yet.
        $this->requireNewNodeAggregateIdsToNotExist(
            $contentGraph,
            $command->nodeAggregateIdMapping
        );

        // Constraint: the parent node must exist in the command's DimensionSpacePoint as well
        $parentNodeAggregate = $this->requireProjectedNodeAggregate(
            $contentGraph,
            $command->targetParentNodeAggregateId
        );
        if ($command->targetSucceedingSiblingNodeAggregateId) {
            $this->requireProjectedNodeAggregate(
                $contentGraph,
                $command->targetSucceedingSiblingNodeAggregateId
            );
        }
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $parentNodeAggregate,
            $command->targetDimensionSpacePoint->toDimensionSpacePoint()
        );

        // Calculate Covered Dimension Space Points: All points being specializations of the
        // given DSP, where the parent also exists.
        $specializations = $this->interDimensionalVariationGraph->getSpecializationSet(
            $command->targetDimensionSpacePoint->toDimensionSpacePoint()
        );
        $coveredDimensionSpacePoints = $specializations->getIntersection(
            $parentNodeAggregate->coveredDimensionSpacePoints
        );

        // Constraint: The node name must be free for a new child of the parent node aggregate
        if ($command->targetNodeName) {
            $this->requireNodeNameToBeUncovered(
                $contentGraph,
                $command->targetNodeName,
                $command->targetParentNodeAggregateId,
            );
        }

        // Now, we can start creating the recursive structure.
        $events = [];
        $this->createEventsForNodeToInsert(
            $contentGraph,
            $command->targetDimensionSpacePoint,
            $coveredDimensionSpacePoints,
            $command->targetParentNodeAggregateId,
            $command->targetSucceedingSiblingNodeAggregateId,
            $command->targetNodeName,
            $command->nodeTreeToInsert,
            $command->nodeAggregateIdMapping,
            $events
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId(
                $contentGraph->getContentStreamId()
            )->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                Events::fromArray($events)
            ),
            $expectedVersion
        );
    }

    private function requireNewNodeAggregateIdsToNotExist(
        ContentGraphInterface $contentGraph,
        Dto\NodeAggregateIdMapping $nodeAggregateIdMapping
    ): void {
        foreach ($nodeAggregateIdMapping->getAllNewNodeAggregateIds() as $nodeAggregateId) {
            $this->requireProjectedNodeAggregateToNotExist(
                $contentGraph,
                $nodeAggregateId
            );
        }
    }

    /**
     * @param array<NodeAggregateWithNodeWasCreated> $events
     */
    private function createEventsForNodeToInsert(
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        NodeAggregateId $targetParentNodeAggregateId,
        ?NodeAggregateId $targetSucceedingSiblingNodeAggregateId,
        ?NodeName $targetNodeName,
        NodeSubtreeSnapshot $nodeToInsert,
        Dto\NodeAggregateIdMapping $nodeAggregateIdMapping,
        array &$events,
    ): void {
        $events[] = new NodeAggregateWithNodeWasCreated(
            $contentGraph->getWorkspaceName(),
            $contentGraph->getContentStreamId(),
            $nodeAggregateIdMapping->getNewNodeAggregateId(
                $nodeToInsert->nodeAggregateId
            ) ?: NodeAggregateId::create(),
            $nodeToInsert->nodeTypeName,
            $originDimensionSpacePoint,
            $targetSucceedingSiblingNodeAggregateId
                ? $this->resolveInterdimensionalSiblingsForCreation(
                    $contentGraph,
                    $targetSucceedingSiblingNodeAggregateId,
                    $originDimensionSpacePoint,
                    $coveredDimensionSpacePoints
                )
                : InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings($coveredDimensionSpacePoints),
            $targetParentNodeAggregateId,
            $targetNodeName,
            $nodeToInsert->propertyValues,
            $nodeToInsert->nodeAggregateClassification,
        );

        foreach ($nodeToInsert->childNodes as $childNodeToInsert) {
            $this->createEventsForNodeToInsert(
                $contentGraph,
                $originDimensionSpacePoint,
                $coveredDimensionSpacePoints,
                // the just-inserted node becomes the new parent node ID
                $nodeAggregateIdMapping->getNewNodeAggregateId(
                    $nodeToInsert->nodeAggregateId
                ) ?: NodeAggregateId::create(),
                // $childNodesToInsert is already in the correct order; so appending only is fine.
                null,
                $childNodeToInsert->nodeName,
                $childNodeToInsert,
                $nodeAggregateIdMapping,
                $events
            );
        }
    }
}
