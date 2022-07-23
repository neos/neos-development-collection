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

namespace Neos\ContentRepository\Feature\NodeReferencing;

use Neos\ContentRepository\Feature\Common\NodeReferenceToWrite;
use Neos\ContentRepository\Feature\Common\SerializedNodeReference;
use Neos\ContentRepository\Feature\Common\SerializedNodeReferences;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\NodeReferencing\Command\SetSerializedNodeReferences;
use Neos\ContentRepository\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\Feature\Common\PropertyScope;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Ramsey\Uuid\Uuid;

trait NodeReferencing
{
    use ConstraintChecks;

    abstract protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager;

    abstract protected function getNodeAggregateEventPublisher(): NodeAggregateEventPublisher;

    abstract protected function getRuntimeBlocker(): RuntimeBlocker;

    public function handleSetNodeReferences(SetNodeReferences $command): CommandResult
    {
        $this->requireContentStreamToExist($command->contentStreamIdentifier);
        $this->requireDimensionSpacePointToExist($command->sourceOriginDimensionSpacePoint->toDimensionSpacePoint());
        $sourceNodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->sourceNodeAggregateIdentifier
        );
        $this->requireNodeAggregateToNotBeRoot($sourceNodeAggregate);
        $nodeTypeName = $sourceNodeAggregate->getNodeTypeName();

        foreach ($command->references as $reference) {
            if ($reference->properties) {
                $this->validateReferenceProperties(
                    $command->referenceName,
                    $reference->properties,
                    $nodeTypeName
                );
            }
        }

        $lowLevelCommand = new SetSerializedNodeReferences(
            $command->contentStreamIdentifier,
            $command->sourceNodeAggregateIdentifier,
            $command->sourceOriginDimensionSpacePoint,
            $command->referenceName,
            SerializedNodeReferences::fromReferences(array_map(
                fn (NodeReferenceToWrite $reference): SerializedNodeReference => new SerializedNodeReference(
                    $reference->targetNodeAggregateIdentifier,
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
            $command->initiatingUserIdentifier
        );

        return $this->handleSetSerializedNodeReferences($lowLevelCommand);
    }

    /**
     * @internal
     * @throws \Neos\ContentRepository\Feature\Common\Exception\ContentStreamDoesNotExistYet
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function handleSetSerializedNodeReferences(SetSerializedNodeReferences $command): CommandResult
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        $this->requireContentStreamToExist($command->contentStreamIdentifier);
        $this->requireDimensionSpacePointToExist(
            $command->sourceOriginDimensionSpacePoint->toDimensionSpacePoint()
        );
        $sourceNodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->sourceNodeAggregateIdentifier
        );
        $this->requireNodeAggregateToNotBeRoot($sourceNodeAggregate);
        $this->requireNodeAggregateToOccupyDimensionSpacePoint(
            $sourceNodeAggregate,
            $command->sourceOriginDimensionSpacePoint
        );
        $this->requireNodeTypeToDeclareReference($sourceNodeAggregate->getNodeTypeName(), $command->referenceName);

        foreach ($command->references as $reference) {
            $destinationNodeAggregate = $this->requireProjectedNodeAggregate(
                $command->contentStreamIdentifier,
                $reference->targetNodeAggregateIdentifier
            );
            $this->requireNodeAggregateToNotBeRoot($destinationNodeAggregate);
            $this->requireNodeAggregateToCoverDimensionSpacePoint(
                $destinationNodeAggregate,
                $command->sourceOriginDimensionSpacePoint->toDimensionSpacePoint()
            );
            $this->requireNodeTypeToAllowNodesOfTypeInReference(
                $sourceNodeAggregate->getNodeTypeName(),
                $command->referenceName,
                $destinationNodeAggregate->getNodeTypeName()
            );
        }

        $domainEvents = DomainEvents::createEmpty();
        $this->getNodeAggregateEventPublisher()->withCommand(
            $command,
            function () use ($command, &$domainEvents, $sourceNodeAggregate) {
                $sourceNodeType = $this->requireNodeType($sourceNodeAggregate->getNodeTypeName());
                $scopeDeclaration = $sourceNodeType->getProperties()[(string)$command->referenceName]['scope'] ?? '';
                $scope = PropertyScope::tryFrom($scopeDeclaration) ?: PropertyScope::SCOPE_NODE;

                $affectedOrigins = $scope->resolveAffectedOrigins(
                    $command->sourceOriginDimensionSpacePoint,
                    $sourceNodeAggregate,
                    $this->interDimensionalVariationGraph
                );

                $event = DecoratedEvent::addIdentifier(
                    new NodeReferencesWereSet(
                        $command->contentStreamIdentifier,
                        $command->sourceNodeAggregateIdentifier,
                        $affectedOrigins,
                        $command->referenceName,
                        $command->references,
                        $command->initiatingUserIdentifier
                    ),
                    Uuid::uuid4()->toString()
                );

                $domainEvents = DomainEvents::withSingleEvent($event);

                $this->getNodeAggregateEventPublisher()->publishMany(
                    ContentStreamEventStreamName::fromContentStreamIdentifier($command->contentStreamIdentifier)
                        ->getEventStreamName(),
                    $domainEvents
                );
            }
        );

        return CommandResult::fromPublishedEvents($domainEvents, $this->getRuntimeBlocker());
    }
}
