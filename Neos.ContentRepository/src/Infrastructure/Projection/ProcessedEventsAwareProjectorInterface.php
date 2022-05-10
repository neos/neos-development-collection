<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Infrastructure\Projection;

use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventListener\AfterInvokeInterface;
use Neos\EventSourcing\EventListener\AppliedEventsStorage\AppliedEventsStorageInterface;
use Neos\EventSourcing\Projection\ProjectorInterface;

interface ProcessedEventsAwareProjectorInterface extends
    ProjectorInterface,
    AfterInvokeInterface,
    AppliedEventsStorageInterface
{
    public function assumeProjectorRunsSynchronously(): void;

    public function hasProcessed(DomainEvents $events): bool;
}
