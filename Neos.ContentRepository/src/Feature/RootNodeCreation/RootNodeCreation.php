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

namespace Neos\ContentRepository\Feature\RootNodeCreation;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\EventStore\Events;
use Neos\ContentRepository\EventStore\EventsToPublish;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Feature\Common\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateCurrentlyExists;
use Neos\ContentRepository\Feature\Common\Exception\NodeTypeIsNotOfTypeRoot;
use Neos\ContentRepository\Feature\Common\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal implementation detail of Command Handlers
 */
trait RootNodeCreation
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
        $this->requireContentStreamToExist($command->contentStreamIdentifier, $contentRepository);
        $this->requireProjectedNodeAggregateToNotExist(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier,
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

        $contentStreamEventStream = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $command->contentStreamIdentifier
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
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier,
            $command->nodeTypeName,
            $coveredDimensionSpacePoints,
            NodeAggregateClassification::CLASSIFICATION_ROOT,
            $command->initiatingUserIdentifier
        );
    }
}
