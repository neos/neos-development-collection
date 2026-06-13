<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\PerformanceTracing;

/**
 * Well-known, named instrumentation points passed to {@see PerformanceTracerInterface::openSpan()}
 * and {@see PerformanceTracerInterface::mark()}.
 *
 * Using an enum (instead of a bare string) for the points the Content Repository emits itself makes them
 * type-safe, refactor-safe and discoverable: a tracer implementation that wants to react to a specific
 * point can compare against an enum case (e.g. `$name === TracePoint::ProjectionApply`) instead of a
 * magic string. Ad-hoc / third-party instrumentation may still pass a plain string – hence the tracer
 * methods accept `string|TracePoint`.
 *
 * NOTE: A point that consumers are expected to react to MUST always be emitted as the enum case (never as
 * the equivalent literal string), otherwise `=== TracePoint::X` matching would silently miss those sites.
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
}
