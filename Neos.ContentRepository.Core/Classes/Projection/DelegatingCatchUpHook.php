<?php

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\EventStore\Model\EventEnvelope;

/**
 * Internal helper class for running *multiple* CatchUpHooks inside
 * a Projection update cycle.
 *
 * @internal
 */
class DelegatingCatchUpHook implements CatchUpHookInterface
{
    /**
     * @var CatchUpHookInterface[]
     */
    private array $catchUpHooks;

    public function __construct(
        CatchUpHookInterface ...$catchUpHooks
    ) {
        $this->catchUpHooks = $catchUpHooks;
    }

    public function onBeforeCatchUp(): void
    {
        foreach ($this->catchUpHooks as $catchUpHook) {
            $catchUpHook->onBeforeCatchUp();
        }
    }

    public function onBeforeEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        foreach ($this->catchUpHooks as $catchUpHook) {
            $catchUpHook->onBeforeEvent($eventInstance, $eventEnvelope);
        }
    }

    public function onAfterEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        foreach ($this->catchUpHooks as $catchUpHook) {
            $catchUpHook->onAfterEvent($eventInstance, $eventEnvelope);
        }
    }

    public function onBeforeBatchCompleted(): void
    {
        foreach ($this->catchUpHooks as $catchUpHook) {
            $catchUpHook->onBeforeBatchCompleted();
        }
    }

    public function onAfterCatchUp(): void
    {
        foreach ($this->catchUpHooks as $catchUpHook) {
            $catchUpHook->onAfterCatchUp();
        }
    }
}
