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

use Neos\ContentRepository\CommandHandler\CommandHandlerInterface;
use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\EventStore\Events;
use Neos\ContentRepository\EventStore\EventsToPublish;
use Neos\ContentRepository\Feature\Common\Exception\NodeConstraintException;
use Neos\ContentRepository\Feature\NodeDuplication\Command\NodeSubtreeSnapshot;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\Feature\NodeDuplication\Command\NodeAggregateIdentifierMapping;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\NodeDuplication\Command\CopyNodesRecursively;
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
        return $command instanceof CopyNodesRecursively;
    }

    public function handle(CommandInterface $command, ContentRepository $contentRepository): EventsToPublish
    {
        if ($command instanceof CopyNodesRecursively) {
            return $this->handleCopyNodesRecursively($command, $contentRepository);
        }

        throw new \RuntimeException('Command not supported');
    }

    /**
     * @throws NodeConstraintException
     */
    private function handleCopyNodesRecursively(
        CopyNodesRecursively $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        // Basic constraints (Content Stream / Dimension Space Point / Node Type of to-be-inserted root node)
        $this->requireContentStreamToExist($command->contentStreamIdentifier, $contentRepository);
        $this->requireDimensionSpacePointToExist(
            $command->targetDimensionSpacePoint->toDimensionSpacePoint()
        );
        $nodeType = $this->requireNodeType($command->nodeTreeToInsert->nodeTypeName);
        $this->requireNodeTypeToNotBeOfTypeRoot($nodeType);

        // Constraint: Does the target parent node allow nodes of this type?
        // NOTE: we only check this for the *root* node of the to-be-inserted structure; and not for its
        // children (as we want to create the structure as-is; assuming it was already valid beforehand).
        $this->requireConstraintsImposedByAncestorsAreMet(
            $command->contentStreamIdentifier,
            $nodeType,
            $command->targetNodeName,
            [$command->targetParentNodeAggregateIdentifier],
            $contentRepository
        );

        // Constraint: The new nodeAggregateIdentifiers are not allowed to exist yet.
        $this->requireNewNodeAggregateIdentifiersToNotExist(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifierMapping,
            $contentRepository
        );

        // Constraint: the parent node must exist in the command's DimensionSpacePoint as well
        $parentNodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->targetParentNodeAggregateIdentifier,
            $contentRepository
        );
        if ($command->targetSucceedingSiblingNodeAggregateIdentifier) {
            $this->requireProjectedNodeAggregate(
                $command->contentStreamIdentifier,
                $command->targetSucceedingSiblingNodeAggregateIdentifier,
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
                $command->contentStreamIdentifier,
                $command->targetNodeName,
                $command->targetParentNodeAggregateIdentifier,
                $command->targetDimensionSpacePoint,
                $coveredDimensionSpacePoints,
                $contentRepository
            );
        }

        // Now, we can start creating the recursive structure.
        $events = [];
        $this->createEventsForNodeToInsert(
            $command->contentStreamIdentifier,
            $command->targetDimensionSpacePoint,
            $coveredDimensionSpacePoints,
            $command->targetParentNodeAggregateIdentifier,
            $command->targetSucceedingSiblingNodeAggregateIdentifier,
            $command->targetNodeName,
            $command->nodeTreeToInsert,
            $command->nodeAggregateIdentifierMapping,
            $command->initiatingUserIdentifier,
            $events
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamIdentifier(
                $command->contentStreamIdentifier
            )->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                Events::fromArray($events)
            ),
            ExpectedVersion::ANY()
        );
    }

    private function requireNewNodeAggregateIdentifiersToNotExist(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifierMapping $nodeAggregateIdentifierMapping,
        ContentRepository $contentRepository
    ): void {
        foreach ($nodeAggregateIdentifierMapping->getAllNewNodeAggregateIdentifiers() as $nodeAggregateIdentifier) {
            $this->requireProjectedNodeAggregateToNotExist(
                $contentStreamIdentifier,
                $nodeAggregateIdentifier,
                $contentRepository
            );
        }
    }

    /**
     * @param array<NodeAggregateWithNodeWasCreated> $events
     */
    private function createEventsForNodeToInsert(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        NodeAggregateIdentifier $targetParentNodeAggregateIdentifier,
        ?NodeAggregateIdentifier $targetSucceedingSiblingNodeAggregateIdentifier,
        ?NodeName $targetNodeName,
        NodeSubtreeSnapshot $nodeToInsert,
        NodeAggregateIdentifierMapping $nodeAggregateIdentifierMapping,
        UserIdentifier $initiatingUserIdentifier,
        array &$events
    ): void {
        $events[] = new NodeAggregateWithNodeWasCreated(
            $contentStreamIdentifier,
            $nodeAggregateIdentifierMapping->getNewNodeAggregateIdentifier(
                $nodeToInsert->nodeAggregateIdentifier
            ) ?: NodeAggregateIdentifier::create(),
            $nodeToInsert->nodeTypeName,
            $originDimensionSpacePoint,
            $coveredDimensionSpacePoints,
            $targetParentNodeAggregateIdentifier,
            $targetNodeName,
            $nodeToInsert->propertyValues,
            $nodeToInsert->nodeAggregateClassification,
            $initiatingUserIdentifier,
            $targetSucceedingSiblingNodeAggregateIdentifier
        );

        foreach ($nodeToInsert->childNodes as $childNodeToInsert) {
            $this->createEventsForNodeToInsert(
                $contentStreamIdentifier,
                $originDimensionSpacePoint,
                $coveredDimensionSpacePoints,
                // the just-inserted node becomes the new parent node Identifier
                $nodeAggregateIdentifierMapping->getNewNodeAggregateIdentifier(
                    $nodeToInsert->nodeAggregateIdentifier
                ) ?: NodeAggregateIdentifier::create(),
                // $childNodesToInsert is already in the correct order; so appending only is fine.
                null,
                $childNodeToInsert->nodeName,
                $childNodeToInsert,
                $nodeAggregateIdentifierMapping,
                $initiatingUserIdentifier,
                $events
            );
        }
    }
}
