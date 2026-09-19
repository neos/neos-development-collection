<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\EventsConcurrentWorkspaceRebases;

use Neos\EventStore\Model\Event\CorrelationId;
use Neos\EventStore\Model\EventEnvelope;

final readonly class RebaseWorkspaceCorrelationId
{
    private function __construct()
    {
    }

    /**
     * @param array<EventEnvelope> $events
     */
    public static function fromEvents(array $events): CorrelationId
    {
        if ($events === []) {
            throw new \InvalidArgumentException('Events are empty', 1785008755);
        }
        $correlationId = null;
        foreach ($events as $eventEnvelope) {
            if ($correlationId === null) {
                if ($eventEnvelope->event->correlationId === null || !str_starts_with($eventEnvelope->event->correlationId->value, 'RebaseWorkspace_')) {
                    throw new \RuntimeException(sprintf('Expected no events or another RebaseWorkspace sequence. Got at %d type %s with %s', $eventEnvelope->sequenceNumber->value, $eventEnvelope->event->type->value, $eventEnvelope->event->correlationId?->value));
                }
                $correlationId = $eventEnvelope->event->correlationId;
                continue;
            }
            if ($correlationId->value !== $eventEnvelope->event->correlationId?->value) {
                throw new \RuntimeException(sprintf('Expected RebaseWorkspace (%s). Got at %d type %s with %s', $correlationId->value, $eventEnvelope->sequenceNumber->value, $eventEnvelope->event->type->value, $eventEnvelope->event->correlationId?->value));
            }
        }
        return $correlationId;
    }
}
