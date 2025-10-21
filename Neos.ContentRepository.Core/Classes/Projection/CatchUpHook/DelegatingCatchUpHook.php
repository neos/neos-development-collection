<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\CatchUpHook;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\EventEnvelope;

/**
 * Internal helper class for running *multiple* CatchUpHooks inside
 * a Projection update cycle.
 *
 * @internal
 */
final readonly class DelegatingCatchUpHook implements CatchUpHookInterface
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

    public function onBeforeCatchUp(SubscriptionStatus $subscriptionStatus): void
    {
        $this->delegateHooks(
            fn (CatchUpHookInterface $catchUpHook) => $catchUpHook->onBeforeCatchUp($subscriptionStatus),
            'onBeforeCatchUp'
        );
    }

    public function onBeforeEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        $this->delegateHooks(
            fn (CatchUpHookInterface $catchUpHook) => $catchUpHook->onBeforeEvent($eventInstance, $eventEnvelope),
            'onBeforeEvent'
        );
    }

    public function onAfterEvent(EventInterface $eventInstance, EventEnvelope $eventEnvelope): void
    {
        $this->delegateHooks(
            fn (CatchUpHookInterface $catchUpHook) => $catchUpHook->onAfterEvent($eventInstance, $eventEnvelope),
            'onAfterEvent'
        );
    }

    public function onAfterBatchCompleted(): void
    {
        $this->delegateHooks(
            fn (CatchUpHookInterface $catchUpHook) => $catchUpHook->onAfterBatchCompleted(),
            'onAfterBatchCompleted'
        );
    }

    public function onAfterCatchUp(): void
    {
        $this->delegateHooks(
            fn (CatchUpHookInterface $catchUpHook) => $catchUpHook->onAfterCatchUp(),
            'onAfterCatchUp'
        );
    }

    /**
     * @param \Closure(CatchUpHookInterface): void $closure
     * @return void
     */
    private function delegateHooks(\Closure $closure, string $hookName): void
    {
        $firstError = null;
        /** @var list<string> $errors */
        $errors = [];
        foreach ($this->catchUpHooks as $catchUpHook) {
            try {
                $closure($catchUpHook);
            } catch (\Throwable $e) {
                $firstError ??= $e;
                $failedCatchupHookName = substr(strrchr($catchUpHook::class, '\\') ?: '', 1);
                $errors[] = sprintf('"%s": %s', $failedCatchupHookName, $e->getMessage());
            }
        }
        if ($firstError !== null) {
            throw new CatchUpHookFailed(
                sprintf('Hook "%s" failed: %s', $hookName, join(";\n", $errors)),
                1733243960,
                $firstError
            );
        }
    }
}
