<?php

namespace Neos\Neos\Fusion\Cache;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Projection\CatchUpHookInterface;
use Neos\EventStore\Model\EventEnvelope;
use Neos\Neos\PendingChangesProjection\ChangeFinder;

/**
 * @internal
 */
class WorkspaceProjectorCatchUpHookForCacheFlushing implements CatchUpHookInterface
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly ContentCacheFlusher $contentCacheFlusher
    ) {
    }

    public function onBeforeCatchUp(): void
    {
    }

    public function onBeforeEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        if ($eventInstance instanceof WorkspaceWasDiscarded) {
            $changeFinder = $this->contentRepository->projectionState(ChangeFinder::class);
            $changes = $changeFinder->findByContentStreamId($eventInstance->previousContentStreamId);

            foreach ($changes as $change) {
                $this->contentCacheFlusher->flushNodeAggregate(
                    $this->contentRepository,
                    $eventInstance->workspaceName,
                    $change->nodeAggregateId
                );
            }
        }
    }

    public function onAfterEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
    }

    public function onBeforeBatchCompleted(): void
    {
    }

    public function onAfterCatchUp(): void
    {
    }
}
