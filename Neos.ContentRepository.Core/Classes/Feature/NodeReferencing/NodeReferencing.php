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
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeReferencing
{
    use ConstraintChecks;

    abstract protected function requireProjectedNodeAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        ContentRepository $contentRepository
    ): NodeAggregate;


    private function handleSetNodeReferences(
        SetNodeReferences $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamId, $contentRepository);
        $this->requireDimensionSpacePointToExist($command->sourceOriginDimensionSpacePoint->toDimensionSpacePoint());
        $sourceNodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamId,
            $command->sourceNodeAggregateId,
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
            $command->contentStreamId,
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
            $command->initiatingUserId
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
        $this->requireContentStreamToExist($command->contentStreamId, $contentRepository);
        $this->requireDimensionSpacePointToExist(
            $command->sourceOriginDimensionSpacePoint->toDimensionSpacePoint()
        );
        $sourceNodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamId,
            $command->sourceNodeAggregateId,
            $contentRepository
        );
        $this->requireNodeAggregateToNotBeRoot($sourceNodeAggregate);
        $this->requireNodeAggregateToOccupyDimensionSpacePoint(
            $sourceNodeAggregate,
            $command->sourceOriginDimensionSpacePoint
        );
        $this->requireNodeTypeToDeclareReference($sourceNodeAggregate->nodeTypeName, $command->referenceName);

        foreach ($command->references as $reference) {
            assert($reference instanceof SerializedNodeReference);
            $destinationNodeAggregate = $this->requireProjectedNodeAggregate(
                $command->contentStreamId,
                $reference->targetNodeAggregateId,
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
                $command->contentStreamId,
                $command->sourceNodeAggregateId,
                $affectedOrigins,
                $command->referenceName,
                $command->references,
                $command->initiatingUserId
            )
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($command->contentStreamId)
                ->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                $events
            ),
            ExpectedVersion::ANY()
        );
    }
}
