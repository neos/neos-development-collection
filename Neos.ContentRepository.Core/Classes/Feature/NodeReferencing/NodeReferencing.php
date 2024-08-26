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

namespace Neos\ContentRepository\Core\Feature\NodeReferencing;

use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\Common\NodeReferencingInternals;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyScope;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetSerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferenceNameToEmpty;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReference;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeReferencing
{
    use ConstraintChecks;
    use NodeReferencingInternals;

    abstract protected function requireProjectedNodeAggregate(
        ContentGraphInterface $contentGraph,
        NodeAggregateId $nodeAggregateId
    ): NodeAggregate;


    private function handleSetNodeReferences(
        SetNodeReferences $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        $this->requireContentStream($command->workspaceName, $commandHandlingDependencies);
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $this->requireDimensionSpacePointToExist($command->sourceOriginDimensionSpacePoint->toDimensionSpacePoint());
        $sourceNodeAggregate = $this->requireProjectedNodeAggregate(
            $contentGraph,
            $command->sourceNodeAggregateId
        );
        $this->requireNodeAggregateToNotBeRoot($sourceNodeAggregate);
        $nodeTypeName = $sourceNodeAggregate->nodeTypeName;

        foreach ($command->references as $reference) {
            if ($reference instanceof NodeReferenceNameToEmpty) {
                continue;
            }
            if ($reference->properties) {
                $this->validateReferenceProperties(
                    $reference->referenceName,
                    $reference->properties,
                    $nodeTypeName
                );
            }
        }

        $lowLevelCommand = SetSerializedNodeReferences::create(
            $command->workspaceName,
            $command->sourceNodeAggregateId,
            $command->sourceOriginDimensionSpacePoint,
            $this->mapNodeReferencesToSerializedNodeReferences($command->references, $nodeTypeName),
        );

        return $this->handleSetSerializedNodeReferences($lowLevelCommand, $commandHandlingDependencies);
    }

    /**
     * @throws ContentStreamDoesNotExistYet
     */
    private function handleSetSerializedNodeReferences(
        SetSerializedNodeReferences $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $expectedVersion = $this->getExpectedVersionOfContentStream($contentGraph->getContentStreamId(), $commandHandlingDependencies);
        $this->requireDimensionSpacePointToExist(
            $command->sourceOriginDimensionSpacePoint->toDimensionSpacePoint()
        );
        $sourceNodeAggregate = $this->requireProjectedNodeAggregate(
            $contentGraph,
            $command->sourceNodeAggregateId
        );
        $this->requireNodeAggregateToNotBeRoot($sourceNodeAggregate);
        $this->requireNodeAggregateToOccupyDimensionSpacePoint(
            $sourceNodeAggregate,
            $command->sourceOriginDimensionSpacePoint
        );

        $sourceNodeType = $this->requireNodeType($sourceNodeAggregate->nodeTypeName);
        $events = [];

        $this->requireNodeTypeToAllowNumberOfReferencesInReference(
            $command->references,
            $sourceNodeAggregate->nodeTypeName
        );

        foreach ($command->references->getReferenceNames() as $referenceName) {
            $this->requireNodeTypeToDeclareReference($sourceNodeAggregate->nodeTypeName, $referenceName);
            $scopeDeclaration = $sourceNodeType->getReferences()[$referenceName->value]['scope'] ?? '';
            $scope = PropertyScope::tryFrom($scopeDeclaration) ?: PropertyScope::SCOPE_NODE;
            // TODO: Optimize affected sets into one event
            $affectedReferences = $command->references->getForReferenceName($referenceName);

            foreach ($affectedReferences as $reference) {
                assert($reference instanceof SerializedNodeReference || $reference instanceof NodeReferenceNameToEmpty);
                if ($reference instanceof NodeReferenceNameToEmpty) {
                    continue;
                }
                $destinationNodeAggregate = $this->requireProjectedNodeAggregate(
                    $contentGraph,
                    $reference->targetNodeAggregateId
                );
                $this->requireNodeAggregateToNotBeRoot($destinationNodeAggregate);
                $this->requireNodeAggregateToCoverDimensionSpacePoint(
                    $destinationNodeAggregate,
                    $command->sourceOriginDimensionSpacePoint->toDimensionSpacePoint()
                );
                $this->requireNodeTypeToAllowNodesOfTypeInReference(
                    $sourceNodeAggregate->nodeTypeName,
                    $reference->referenceName,
                    $destinationNodeAggregate->nodeTypeName
                );
            }

            $affectedOrigins = $scope->resolveAffectedOrigins(
                $command->sourceOriginDimensionSpacePoint,
                $sourceNodeAggregate,
                $this->interDimensionalVariationGraph
            );

            $events[] = new NodeReferencesWereSet(
                $contentGraph->getWorkspaceName(),
                $contentGraph->getContentStreamId(),
                $command->sourceNodeAggregateId,
                $affectedOrigins,
                $affectedReferences,
            );
        }

        $events = Events::fromArray($events);

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId())
                ->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                $events
            ),
            $expectedVersion
        );
    }
}
