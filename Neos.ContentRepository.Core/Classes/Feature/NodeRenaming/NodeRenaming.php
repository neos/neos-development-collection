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

use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Event\NodeAggregateNameWasChanged;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeRenaming
{
    use ConstraintChecks;

    private function handleChangeNodeAggregateName(ChangeNodeAggregateName $command, CommandHandlingDependencies $commandHandlingDependencies): EventsToPublish
    {
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $this->requireContentStream($command->workspaceName, $commandHandlingDependencies);
        $expectedVersion = $this->getExpectedVersionOfContentStream($contentGraph->getContentStreamId(), $commandHandlingDependencies);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentGraph,
            $command->nodeAggregateId
        );
        $this->requireNodeAggregateToNotBeRoot($nodeAggregate, 'and Root Node Aggregates cannot be renamed');
        $this->requireNodeAggregateToBeUntethered($nodeAggregate);
        foreach ($contentGraph->findParentNodeAggregates($command->nodeAggregateId) as $parentNodeAggregate) {
            $this->requireNodeNameToBeUncovered(
                $contentGraph,
                $command->newNodeName,
                $parentNodeAggregate->nodeAggregateId,
            );
            $this->requireNodeTypeNotToDeclareTetheredChildNodeName($parentNodeAggregate->nodeTypeName, $command->newNodeName);
        }

        $events = Events::with(
            new NodeAggregateNameWasChanged(
                $contentGraph->getWorkspaceName(),
                $contentGraph->getContentStreamId(),
                $command->nodeAggregateId,
                $command->newNodeName,
            ),
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId())->getEventStreamName(),
            NodeAggregateEventPublisher::enrichWithCommand(
                $command,
                $events
            ),
            $expectedVersion
        );
    }
}
