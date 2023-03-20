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

namespace Neos\ContentRepository\Core\Feature\RootNodeCreation;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateDimensionsWereUpdated;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsNotRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyExists;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsNotOfTypeRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal implementation detail of Command Handlers
 */
trait RootNodeHandling
{
    abstract protected function getAllowedDimensionSubspace(): DimensionSpacePointSet;

    abstract protected function requireNodeType(NodeTypeName $nodeTypeName): NodeType;

    abstract protected function requireNodeTypeToNotBeAbstract(NodeType $nodeType): void;

    abstract protected function requireNodeTypeToBeOfTypeRoot(NodeType $nodeType): void;

    /**
     * @param CreateRootNodeAggregateWithNode $command
     * @return EventsToPublish
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeAggregateCurrentlyExists
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws NodeTypeNotFound
     * @throws NodeTypeIsNotOfTypeRoot
     */
    private function handleCreateRootNodeAggregateWithNode(
        CreateRootNodeAggregateWithNode $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamId, $contentRepository);
        $this->requireProjectedNodeAggregateToNotExist(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        $nodeType = $this->requireNodeType($command->nodeTypeName);
        $this->requireNodeTypeToNotBeAbstract($nodeType);
        $this->requireNodeTypeToBeOfTypeRoot($nodeType);

        $events = Events::with(
            $this->createRootWithNode(
                $command,
                $this->getAllowedDimensionSubspace()
            )
        );

        $contentStreamEventStream = ContentStreamEventStreamName::fromContentStreamId(
            $command->contentStreamId
        );
        return new EventsToPublish(
            $contentStreamEventStream->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                $events
            ),
            ExpectedVersion::ANY()
        );
    }

    private function createRootWithNode(
        CreateRootNodeAggregateWithNode $command,
        DimensionSpacePointSet $coveredDimensionSpacePoints
    ): RootNodeAggregateWithNodeWasCreated {
        return new RootNodeAggregateWithNodeWasCreated(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $command->nodeTypeName,
            $coveredDimensionSpacePoints,
            NodeAggregateClassification::CLASSIFICATION_ROOT,
        );
    }

    /**
     * @param UpdateRootNodeAggregateDimensions $command
     * @return EventsToPublish
     */
    private function handleUpdateRootNodeAggregateDimensions(
        UpdateRootNodeAggregateDimensions $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamId, $contentRepository);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        if (!$nodeAggregate->classification->isRoot()) {
            throw new NodeAggregateIsNotRoot('The node aggregate ' . $nodeAggregate->nodeAggregateId->value . ' is not classified as root, but should be for command UpdateRootNodeAggregateDimensions.', 1678647355);
        }

        $events = Events::with(
            new RootNodeAggregateDimensionsWereUpdated(
                $command->contentStreamId,
                $command->nodeAggregateId,
                $this->getAllowedDimensionSubspace()
            )
        );

        $contentStreamEventStream = ContentStreamEventStreamName::fromContentStreamId(
            $command->contentStreamId
        );
        return new EventsToPublish(
            $contentStreamEventStream->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                $events
            ),
            ExpectedVersion::ANY()
        );
    }
}
