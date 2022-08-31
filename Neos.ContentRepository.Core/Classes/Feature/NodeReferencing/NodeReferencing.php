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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferenceToWrite;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyScope;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReference;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetSerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeReferencing
{
    use ConstraintChecks;

    abstract protected function requireProjectedNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        ContentRepository $contentRepository
    ): NodeAggregate;


    private function handleSetNodeReferences(
        SetNodeReferences $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamIdentifier, $contentRepository);
        $this->requireDimensionSpacePointToExist($command->sourceOriginDimensionSpacePoint->toDimensionSpacePoint());
        $sourceNodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->sourceNodeAggregateIdentifier,
            $contentRepository
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

        $lowLevelCommand = new SetSerializedNodeReferences(
            $command->contentStreamIdentifier,
            $command->sourceNodeAggregateIdentifier,
            $command->sourceOriginDimensionSpacePoint,
            $command->referenceName,
            Dto\SerializedNodeReferences::fromReferences(array_map(
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

        return $this->handleSetSerializedNodeReferences($lowLevelCommand, $contentRepository);
    }

    /**
     * @throws \Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    private function handleSetSerializedNodeReferences(
        SetSerializedNodeReferences $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamIdentifier, $contentRepository);
        $this->requireDimensionSpacePointToExist(
            $command->sourceOriginDimensionSpacePoint->toDimensionSpacePoint()
        );
        $sourceNodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->sourceNodeAggregateIdentifier,
            $contentRepository
        );
        $this->requireNodeAggregateToNotBeRoot($sourceNodeAggregate);
        $this->requireNodeAggregateToOccupyDimensionSpacePoint(
            $sourceNodeAggregate,
            $command->sourceOriginDimensionSpacePoint
        );
        $this->requireNodeTypeToDeclareReference($sourceNodeAggregate->nodeTypeName, $command->referenceName);

        foreach ($command->references as $reference) {
            $destinationNodeAggregate = $this->requireProjectedNodeAggregate(
                $command->contentStreamIdentifier,
                $reference->targetNodeAggregateIdentifier,
                $contentRepository
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
        $scopeDeclaration = $sourceNodeType->getProperties()[(string)$command->referenceName]['scope'] ?? '';
        $scope = PropertyScope::tryFrom($scopeDeclaration) ?: PropertyScope::SCOPE_NODE;

        $affectedOrigins = $scope->resolveAffectedOrigins(
            $command->sourceOriginDimensionSpacePoint,
            $sourceNodeAggregate,
            $this->interDimensionalVariationGraph
        );

        $events = Events::with(
            new NodeReferencesWereSet(
                $command->contentStreamIdentifier,
                $command->sourceNodeAggregateIdentifier,
                $affectedOrigins,
                $command->referenceName,
                $command->references,
                $command->initiatingUserIdentifier
            )
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamIdentifier($command->contentStreamIdentifier)
                ->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                $events
            ),
            ExpectedVersion::ANY()
        );
    }
}
