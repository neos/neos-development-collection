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

namespace Neos\ContentRepository\Core\Feature\NodeRenaming;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Event\NodeAggregateNameWasChanged;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeRenaming
{
    use ConstraintChecks;

    private function handleChangeNodeAggregateName(ChangeNodeAggregateName $command, ContentRepository $contentRepository): EventsToPublish
    {
        $contentStreamId = $this->requireContentStream($command->workspaceName, $contentRepository);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentStreamId,
            $command->nodeAggregateId,
            $contentRepository
        );
        $this->requireNodeAggregateToNotBeRoot($nodeAggregate, 'and Root Node Aggregates cannot be renamed');
        $this->requireNodeAggregateToBeUntethered($nodeAggregate);
        foreach ($contentRepository->getContentGraph()->findParentNodeAggregates($contentStreamId, $command->nodeAggregateId) as $parentNodeAggregate) {
            foreach ($parentNodeAggregate->occupiedDimensionSpacePoints as $occupiedParentDimensionSpacePoint) {
                $this->requireNodeNameToBeUnoccupied(
                    $contentStreamId,
                    $command->newNodeName,
                    $parentNodeAggregate->nodeAggregateId,
                    $occupiedParentDimensionSpacePoint,
                    $parentNodeAggregate->coveredDimensionSpacePoints,
                    $contentRepository
                );
            }
        }

        $events = Events::with(
            new NodeAggregateNameWasChanged(
                $contentStreamId,
                $command->nodeAggregateId,
                $command->newNodeName,
            ),
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentStreamId)->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                $events
            ),
            ExpectedVersion::ANY()
        );
    }
}
