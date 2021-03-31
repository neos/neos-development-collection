<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Infrastructure\Projection\ProcessedEventsAwareProjectorCollection;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class CommandResult
{
    private DomainEvents $publishedEvents;

    private RuntimeBlocker $runtimeBlocker;

    protected function __construct(DomainEvents $publishedEvents, RuntimeBlocker $runtimeBlocker)
    {
        $this->publishedEvents = $publishedEvents;
        $this->runtimeBlocker = $runtimeBlocker;
    }

    public static function fromPublishedEvents(DomainEvents $events, RuntimeBlocker $runtimeBlocker): self
    {
        return new self($events, $runtimeBlocker);
    }

    public static function createEmpty(RuntimeBlocker $runtimeBlocker): self
    {
        return new self(DomainEvents::createEmpty(), $runtimeBlocker);
    }

    public function merge(CommandResult $other): self
    {
        return self::fromPublishedEvents(
            $this->publishedEvents->appendEvents($other->getPublishedEvents()),
            $this->runtimeBlocker
        );
    }

    public function getPublishedEvents(): DomainEvents
    {
        return $this->publishedEvents;
    }

    public function blockUntilProjectionsAreUpToDate(
        ProcessedEventsAwareProjectorCollection $projectorsToBeBlocked = null
    ): void {
        $this->runtimeBlocker->blockUntilProjectionsAreUpToDate($this, $projectorsToBeBlocked);
    }
}
