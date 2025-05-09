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

namespace Neos\ContentRepository\Core\Feature\WorkspaceRebase;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsNodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\EventStore\Model\Event\SequenceNumber;

/**
 * @api part of the exception exposed when rebasing failed
 */
final readonly class ConflictingEvent
{
    /**
     * @internal
     */
    public function __construct(
        private EventInterface $event,
        private \Throwable $exception,
        private SequenceNumber $sequenceNumber,
    ) {
    }

    /**
     * The node aggregate id of the conflicting event
     */
    public function getAffectedNodeAggregateId(): ?NodeAggregateId
    {
        return $this->event instanceof EmbedsNodeAggregateId
            ? $this->event->getNodeAggregateId()
            : null;
    }

    /**
     * The exception for the conflict
     */
    public function getException(): \Throwable
    {
        return $this->exception;
    }

    /**
     * The event store sequence number of the event containing the command to be rebased
     *
     * @internal exposed for testing
     */
    public function getSequenceNumber(): SequenceNumber
    {
        return $this->sequenceNumber;
    }

    /**
     * The event that conflicts
     *
     * @internal exposed for testing and experimental use cases
     */
    public function getEvent(): EventInterface
    {
        return $this->event;
    }
}
