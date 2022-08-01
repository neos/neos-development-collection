<?php

namespace Neos\ContentRepository\Projection;

use Neos\ContentRepository\EventStore\EventInterface;

/**
 * @internal
 */
interface CatchUpHookInterface
{

    public function onBeforeCatchUp(): void;

    public function onBeforeEvent(EventInterface $eventInstance): void;

    public function onAfterEvent(EventInterface $eventInstance): void;

    public function onBeforeBatchCompleted(): void;

    public function onAfterCatchUp(): void;
}
