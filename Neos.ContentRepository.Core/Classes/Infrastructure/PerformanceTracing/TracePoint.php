<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\PerformanceTracing;

/**
 * Well-known, named instrumentation points passed to {@see PerformanceTracerInterface::openSpan()}
 * and {@see PerformanceTracerInterface::mark()}.
 *
 * Using an enum (instead of a bare string) for the points the Content Repository emits itself makes them
 * type-safe, refactor-safe and discoverable: a tracer implementation that wants to react to a specific
 * point can compare against an enum case instead of a magic string.
 * Ad-hoc / third-party instrumentation may still pass a plain string – hence the tracer
 * methods accept `string|TracePoint`.
 *
 * @api (experimental) together with {@see PerformanceTracerInterface}
 */
enum TracePoint: string
{
    // spans
    case ContentRepositoryHandle = 'ContentRepository::handle';
    case SubscriptionEngineCatchUpSubscriptions = 'SubscriptionEngine::catchUpSubscriptions';

    // marks
    case CommandHookOnBeforeHandle = 'CommandHook::onBeforeHandle';
    case AuthProviderCanExecuteCommand = 'AuthProvider::canExecuteCommand';
    case CommandBusHandle = 'CommandBus::handle';
    case EventStoreCommit = 'EventStore::commit';
    case CommandHookOnAfterHandle = 'CommandHook::onAfterHandle';
    case SubscriptionEngineCatchUpActive = 'SubscriptionEngine::catchUpActive';
    case CatchUpHooksOnBeforeCatchUp = 'CatchUpHooks::onBeforeCatchUp';
    case ProjectionApply = 'Projection::apply';

    /**
     * The human-readable name for display/logging; normalises the `string|TracePoint` union.
     */
    public static function nameOf(string|TracePoint $name): string
    {
        return $name instanceof TracePoint ? $name->value : $name;
    }

    public function equals(string|TracePoint $name): bool
    {
        if ($name instanceof TracePoint) {
            return $name === $this;
        }
        return $name === $this->value;
    }
}
