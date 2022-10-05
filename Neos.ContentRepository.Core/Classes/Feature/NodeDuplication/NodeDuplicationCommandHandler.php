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

use Neos\ContentRepository\Core\CommandHandler\CommandHandlerInterface;
use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeConstraintException;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Dto\NodeSubtreeSnapshot;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Command\CopyNodesRecursively;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal from userland, you'll use ContentRepository::handle to dispatch commands
 */
final class NodeDuplicationCommandHandler implements CommandHandlerInterface
{
    use ConstraintChecks;

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

    public function handle(CommandInterface $command, ContentRepository $contentRepository): EventsToPublish
    {
        /** @phpstan-ignore-next-line */
        return match ($command::class) {
            CopyNodesRecursively::class => $this->handleCopyNodesRecursively($command, $contentRepository),
        };
    }

    /**
     * @throws NodeConstraintException
     */
    private function handleCopyNodesRecursively(
        CopyNodesRecursively $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        // Basic constraints (Content Stream / Dimension Space Point / Node Type of to-be-inserted root node)
        $this->requireContentStreamToExist($command->contentStreamId, $contentRepository);
        $this->requireDimensionSpacePointToExist(
            $command->targetDimensionSpacePoint->toDimensionSpacePoint()
        );
        $nodeType = $this->requireNodeType($command->nodeTreeToInsert->nodeTypeName);
        $this->requireNodeTypeToNotBeOfTypeRoot($nodeType);

        // Constraint: Does the target parent node allow nodes of this type?
        // NOTE: we only check this for the *root* node of the to-be-inserted structure; and not for its
        // children (as we want to create the structure as-is; assuming it was already valid beforehand).
        $this->requireConstraintsImposedByAncestorsAreMet(
            $command->contentStreamId,
            $nodeType,
            $command->targetNodeName,
            [$command->targetParentNodeAggregateId],
            $contentRepository
        );

        // Constraint: The new nodeAggregateIds are not allowed to exist yet.
        $this->requireNewNodeAggregateIdsToNotExist(
            $command->contentStreamId,
            $command->nodeAggregateIdMapping,
            $contentRepository
        );

        // Constraint: the parent node must exist in the command's DimensionSpacePoint as well
        $parentNodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamId,
            $command->targetParentNodeAggregateId,
            $contentRepository
        );
        if ($command->targetSucceedingSiblingNodeAggregateId) {
            $this->requireProjectedNodeAggregate(
                $command->contentStreamId,
                $command->targetSucceedingSiblingNodeAggregateId,
                $contentRepository
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

        // Constraint: The node name must be free in all these dimension space points
        if ($command->targetNodeName) {
            $this->requireNodeNameToBeUnoccupied(
                $command->contentStreamId,
                $command->targetNodeName,
                $command->targetParentNodeAggregateId,
                $command->targetDimensionSpacePoint,
                $coveredDimensionSpacePoints,
                $contentRepository
            );
        }

        // Now, we can start creating the recursive structure.
        $events = [];
        $this->createEventsForNodeToInsert(
            $command->contentStreamId,
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
                $command->contentStreamId
            )->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                Events::fromArray($events)
            ),
            ExpectedVersion::ANY()
        );
    }

    private function requireNewNodeAggregateIdsToNotExist(
        ContentStreamId $contentStreamId,
        Dto\NodeAggregateIdMapping $nodeAggregateIdMapping,
        ContentRepository $contentRepository
    ): void {
        foreach ($nodeAggregateIdMapping->getAllNewNodeAggregateIds() as $nodeAggregateId) {
            $this->requireProjectedNodeAggregateToNotExist(
                $contentStreamId,
                $nodeAggregateId,
                $contentRepository
            );
        }
    }

    /**
     * @param array<NodeAggregateWithNodeWasCreated> $events
     */
    private function createEventsForNodeToInsert(
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        NodeAggregateId $targetParentNodeAggregateId,
        ?NodeAggregateId $targetSucceedingSiblingNodeAggregateId,
        ?NodeName $targetNodeName,
        NodeSubtreeSnapshot $nodeToInsert,
        Dto\NodeAggregateIdMapping $nodeAggregateIdMapping,
        array &$events
    ): void {
        $events[] = new NodeAggregateWithNodeWasCreated(
            $contentStreamId,
            $nodeAggregateIdMapping->getNewNodeAggregateId(
                $nodeToInsert->nodeAggregateId
            ) ?: NodeAggregateId::create(),
            $nodeToInsert->nodeTypeName,
            $originDimensionSpacePoint,
            $coveredDimensionSpacePoints,
            $targetParentNodeAggregateId,
            $targetNodeName,
            $nodeToInsert->propertyValues,
            $nodeToInsert->nodeAggregateClassification,
            $targetSucceedingSiblingNodeAggregateId
        );

        foreach ($nodeToInsert->childNodes as $childNodeToInsert) {
            $this->createEventsForNodeToInsert(
                $contentStreamId,
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
