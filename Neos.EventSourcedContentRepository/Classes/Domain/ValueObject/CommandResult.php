<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\DomainEvents;
use Neos\Flow\Annotations as Flow;

final class CommandResult
{
    protected DomainEvents $publishedEvents;

    protected function __construct(DomainEvents $publishedEvents)
    {
        $this->publishedEvents = $publishedEvents;
    }

    public static function fromPublishedEvents(DomainEvents $events): self
    {
        return new self($events);
    }

    public static function createEmpty(): self
    {
        return new self(DomainEvents::createEmpty());
    }

    public function merge(CommandResult $commandResult): self
    {
        return self::fromPublishedEvents($this->publishedEvents->appendEvents($commandResult->publishedEvents));
    }

    public function getPublishedEvents(): DomainEvents
    {
        return $this->publishedEvents;
    }
}
