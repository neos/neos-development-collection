<?php

namespace Neos\ContentRepository\Projection;

use Neos\ContentRepository\EventStore\EventInterface;

interface CatchUpHandlerInterface
{

    public function onBeforeCatchUp(): void;

    public function onBeforeEvent(EventInterface $eventInstance): void;

    public function onAfterEvent(EventInterface $eventInstance): void;

    public function onAfterCatchUp(): void;
}
