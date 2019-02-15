<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\Node;

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

/**
 * @Flow\Proxy(false)
 */
final class NodeCommandResult
{
    /**
     * @var DomainEvents
     */
    private $publishedEvents;

    private function __construct(DomainEvents $publishedEvents)
    {
        $this->publishedEvents = $publishedEvents;
    }

    public static function fromPublishedEvents(DomainEvents $events): self
    {
        return new static($events);
    }

    public function getPublishedEvents(): DomainEvents
    {
        return $this->publishedEvents;
    }
}
