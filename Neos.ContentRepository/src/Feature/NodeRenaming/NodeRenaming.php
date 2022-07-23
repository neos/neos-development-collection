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

namespace Neos\ContentRepository\Feature\NodeRenaming;

use Neos\ContentRepository\EventStore\Events;
use Neos\ContentRepository\EventStore\EventsToPublish;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Feature\NodeRenaming\Event\NodeAggregateNameWasChanged;
use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

trait NodeRenaming
{
    abstract protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager;

    abstract protected function getNodeAggregateEventPublisher(): NodeAggregateEventPublisher;

    private function handleChangeNodeAggregateName(ChangeNodeAggregateName $command): EventsToPublish
    {
        $this->getReadSideMemoryCacheManager()->disableCache();
        // TODO: check if CS exists
        // TODO: check if aggregate exists and delegate to it
        // TODO: check if aggregate is root
        $events = Events::with(
            new NodeAggregateNameWasChanged(
                $command->contentStreamIdentifier,
                $command->nodeAggregateIdentifier,
                $command->newNodeName,
                $command->initiatingUserIdentifier
            ),
        );

        return new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamIdentifier(
                $command->contentStreamIdentifier
            )->getEventStreamName(),
            $this->getNodeAggregateEventPublisher()->enrichWithCommand(
                $command,
                $events
            ),
            ExpectedVersion::ANY()
        );
    }
}
