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

use Neos\ContentRepository\Core\CommandHandler\CommandHandlingDependencies;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Core\Feature\Common\NodeReferencingInternals;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyScope;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetSerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Core\Feature\RebaseableCommand;
use Neos\ContentRepository\Core\NodeType\NodeType;
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

        foreach ($command->references as $referencesByProperty) {
            foreach ($referencesByProperty->references as $reference) {
                if ($reference->properties->values !== []) {
                    $this->validateReferenceProperties(
                        $referencesByProperty->referenceName,
                        $reference->properties,
                        $nodeTypeName
                    );
                }
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

        foreach ($command->references as $referencesForName) {
            $this->requireNodeTypeToDeclareReference($sourceNodeAggregate->nodeTypeName, $referencesForName->referenceName);
            foreach ($referencesForName->references as $reference) {
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
                    $referencesForName->referenceName,
                    $destinationNodeAggregate->nodeTypeName
                );
            }
        }

        foreach (self::splitReferencesByScope($command->references, $sourceNodeType) as $rawScope => $references) {
            $scope = PropertyScope::from($rawScope);
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
                $references,
            );
        }

        if ($events === []) {
            // cannot happen here as the command could not be instantiated without any intention see constructor validation
            throw new \RuntimeException('Cannot handle "SetSerializedNodeReferences" with no references to modify', 1736797975);
        }

        $events = Events::fromArray($events);

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId())
                ->getEventStreamName(),
            RebaseableCommand::enrichWithCommand(
                $command,
                $events
            ),
            $expectedVersion
        );
    }

    /**
     * @return array<string,SerializedNodeReferences>
     */
    private static function splitReferencesByScope(SerializedNodeReferences $nodeReferences, NodeType $nodeType): array
    {
        $referencesByScope = [];
        foreach ($nodeReferences as $nodeReferenceForName) {
            $scopeDeclaration = $nodeType->getReferences()[$nodeReferenceForName->referenceName->value]['scope'] ?? '';
            $scope = PropertyScope::tryFrom($scopeDeclaration) ?: PropertyScope::SCOPE_NODE;
            $referencesByScope[$scope->value][] = $nodeReferenceForName;
        }

        return array_map(
            SerializedNodeReferences::fromArray(...),
            $referencesByScope
        );
    }
}
