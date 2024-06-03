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
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyScope;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetSerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferenceToWrite;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReference;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
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
            if ($reference->properties) {
                $this->validateReferenceProperties(
                    $command->referenceName,
                    $reference->properties,
                    $nodeTypeName
                );
            }
        }

        $lowLevelCommand = SetSerializedNodeReferences::create(
            $command->workspaceName,
            $command->sourceNodeAggregateId,
            $command->sourceOriginDimensionSpacePoint,
            $command->referenceName,
            Dto\SerializedNodeReferences::fromReferences(array_map(
                fn (NodeReferenceToWrite $reference): SerializedNodeReference => new SerializedNodeReference(
                    $reference->targetNodeAggregateId,
                    $reference->properties
                        ? $this->getPropertyConverter()->serializeReferencePropertyValues(
                            $reference->properties,
                            $this->requireNodeType($nodeTypeName),
                            $command->referenceName
                        )
                        : null
                ),
                $command->references->references
            )),
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
        $this->requireNodeTypeToDeclareReference($sourceNodeAggregate->nodeTypeName, $command->referenceName);

        $this->requireNodeTypeToAllowNumberOfReferencesInReference(
            $command->references,
            $command->referenceName,
            $sourceNodeAggregate->nodeTypeName
        );

        foreach ($command->references as $reference) {
            assert($reference instanceof SerializedNodeReference);
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
                $command->referenceName,
                $destinationNodeAggregate->nodeTypeName
            );
        }

        $sourceNodeType = $this->requireNodeType($sourceNodeAggregate->nodeTypeName);
        $scopeDeclaration = $sourceNodeType->getReferences()[$command->referenceName->value]['scope'] ?? '';
        $scope = PropertyScope::tryFrom($scopeDeclaration) ?: PropertyScope::SCOPE_NODE;

        $affectedOrigins = $scope->resolveAffectedOrigins(
            $command->sourceOriginDimensionSpacePoint,
            $sourceNodeAggregate,
            $this->interDimensionalVariationGraph
        );

        $events = Events::with(
            new NodeReferencesWereSet(
                $contentGraph->getWorkspaceName(),
                $contentGraph->getContentStreamId(),
                $command->sourceNodeAggregateId,
                $affectedOrigins,
                $command->referenceName,
                $command->references,
            )
        );

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
