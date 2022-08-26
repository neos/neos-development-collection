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
        $this->requireContentStreamToExist($command->getContentStreamIdentifier(), $contentRepository);
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
            [$command->getTargetParentNodeAggregateIdentifier()],
            $contentRepository
        );

        // Constraint: The new nodeAggregateIdentifiers are not allowed to exist yet.
        $this->requireNewNodeAggregateIdentifiersToNotExist(
            $command->getContentStreamIdentifier(),
            $command->getNodeAggregateIdentifierMapping(),
            $contentRepository
        );

        // Constraint: the parent node must exist in the command's DimensionSpacePoint as well
        $parentNodeAggregate = $this->requireProjectedNodeAggregate(
            $command->getContentStreamIdentifier(),
            $command->getTargetParentNodeAggregateIdentifier(),
            $contentRepository
        );
        if ($command->getTargetSucceedingSiblingNodeAggregateIdentifier()) {
            $this->requireProjectedNodeAggregate(
                $command->getContentStreamIdentifier(),
                $command->getTargetSucceedingSiblingNodeAggregateIdentifier(),
                $contentRepository
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
                $coveredDimensionSpacePoints,
                $contentRepository
            );
        }

        // Now, we can start creating the recursive structure.
        $events = [];
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

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamIdentifier(
                $command->getContentStreamIdentifier()
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
