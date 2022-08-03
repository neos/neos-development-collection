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

namespace Neos\ContentRepository\Infrastructure\Projection;

use Neos\EventSourcing\Event\DomainEvents;
use Neos\Flow\Annotations as Flow;

/**
 * This API will still change a bit.
 *
 * @Flow\Proxy(false)
 */
final class CommandResult
{
    private DomainEvents $publishedEvents;

    /**
     * @var RuntimeBlocker|null the runtimeBlocker MUST be filled if there are domain events
     */
    private ?RuntimeBlocker $runtimeBlocker = null;

    protected function __construct(DomainEvents $publishedEvents, ?RuntimeBlocker $runtimeBlocker)
    {
        if ($runtimeBlocker === null && !$publishedEvents->isEmpty()) {
            throw new \InvalidArgumentException(
                'The Runtime Blocker was not given, although the event list was non-empty. This should never happen.',
                1639313989
            );
        }
        $this->publishedEvents = $publishedEvents;
        $this->runtimeBlocker = $runtimeBlocker;
    }

    public static function fromPublishedEvents(DomainEvents $events, RuntimeBlocker $runtimeBlocker): self
    {
        return new self($events, $runtimeBlocker);
    }

    public static function createEmpty(): self
    {
        return new self(DomainEvents::createEmpty(), null);
    }

    public function merge(self $other): self
    {
        if ($other->publishedEvents->isEmpty()) {
            // the other side has no published events, we do not need to do anything.
            return $this;
        }

        // here, we know the other side is non-empty - so we can simply use the runtime blocker of the other side - as
        // it can be that our own side is empty and has thus no runtime blocker assigned.
        /** @var RuntimeBlocker $runtimeBlocker */
        $runtimeBlocker = $other->runtimeBlocker;
        return self::fromPublishedEvents(
            $this->publishedEvents->appendEvents($other->getPublishedEvents()),
            $runtimeBlocker
        );
    }

    public function getPublishedEvents(): DomainEvents
    {
        return $this->publishedEvents;
    }

    public function blockUntilProjectionsAreUpToDate(
        ProcessedEventsAwareProjectorCollection $projectorsToBeBlocked = null
    ): void {
        if ($this->publishedEvents->isEmpty()) {
            // if published events are empty, $this->runtimeBlocker is NULL as well. Luckily, we do not need to block
            // if there are no events :-)
            return;
        }
        if (is_null($this->runtimeBlocker)) {
            throw new \RuntimeException(
                'No runtime blocker specified for CommandResult,'
                    . ' but is required for ::blockUntilProjectionsAreUpToDate',
                1645362901
            );
        }
        $this->runtimeBlocker->blockUntilProjectionsAreUpToDate($this, $projectorsToBeBlocked);
    }
}
