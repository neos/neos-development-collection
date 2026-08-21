<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\EventStore;

use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\InitiatingEventMetadata;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\Security\Dto\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event\CorrelationId;
use Neos\EventStore\Model\Event\EventId;
use Neos\EventStore\Model\Event\EventMetadata;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class InitiatingEventMetadataTest extends TestCase
{
    #[Test]
    public function enrichEventsWithInitiatingMetadataUsesUtc(): void
    {
        $extracted = InitiatingEventMetadata::enrichEventsWithInitiatingMetadata(
            Events::with(
                new ContentStreamWasCreated(
                    ContentStreamId::fromString('cs-id')
                )
            ),
            UserId::fromString('my-user'),
            \DateTimeImmutable::createFromFormat(\DateTimeImmutable::ATOM, '2024-09-22T12:00:00+01:00')
        );

        self::assertCount(1, $extracted->items);
        $first = $extracted->items[0];
        self::assertInstanceOf(DecoratedEvent::class, $first);
        self::assertEquals([
            'initiatingTimestamp' => '2024-09-22T11:00:00+00:00',
            'initiatingUserId' => 'my-user',
        ], $first->eventMetadata?->value);
    }

    #[Test]
    public function extractInitiatingMetadata(): void
    {
        $metaData = EventMetadata::fromArray(
            [
                'initiatingTimestamp' => '2024-09-22T11:00:00+00:00',
                'initiatingUserId' => 'my-user',
                'myMetaKey' => 'myMetaValue'
            ]
        );

        self::assertEquals(
            [
                'initiatingTimestamp' => '2024-09-22T11:00:00+00:00',
                'initiatingUserId' => 'my-user',
            ],
            InitiatingEventMetadata::extractInitiatingMetadata($metaData)->value
        );
    }

    #[Test]
    public function enrichEventsWithInitiatingMetadataUsesUtcWithExistingMetadata(): void
    {
        $extracted = InitiatingEventMetadata::enrichEventsWithInitiatingMetadata(
            Events::with(
                DecoratedEvent::create(
                    new ContentStreamWasCreated(
                        ContentStreamId::fromString('cs-id')
                    ),
                    eventId: EventId::fromString('81775050-b1bf-4fd2-ba03-07a30c0120e3'),
                    metadata: [
                        'myMetaKey' => 'myMetaValue'
                    ],
                    causationId: EventId::fromString('a1981f58-5a02-4cd1-b660-44238c7271cd'),
                    correlationId: CorrelationId::fromString('my-correlation')
                )
            ),
            UserId::fromString('my-user'),
            \DateTimeImmutable::createFromFormat(\DateTimeImmutable::ATOM, '2024-09-22T12:00:00+01:00')
        );

        self::assertCount(1, $extracted->items);
        $first = $extracted->items[0];
        self::assertInstanceOf(DecoratedEvent::class, $first);
        self::assertEquals([
            'initiatingTimestamp' => '2024-09-22T11:00:00+00:00',
            'initiatingUserId' => 'my-user',
            'myMetaKey' => 'myMetaValue'
        ], $first->eventMetadata?->value);
        self::assertEquals('81775050-b1bf-4fd2-ba03-07a30c0120e3', $first->eventId?->value);
        self::assertEquals('a1981f58-5a02-4cd1-b660-44238c7271cd', $first->causationId?->value);
        self::assertEquals('my-correlation', $first->correlationId?->value);
    }

    public function getInitiatingTimestamp(): void
    {
        $extracted = InitiatingEventMetadata::getInitiatingTimestamp(EventMetadata::fromArray([
            'initiatingTimestamp' => '2024-09-22T12:00:00+00:00'
        ]));
        self::assertEquals('2024-09-22T12:00:00+00:00', $extracted->format(\DateTimeImmutable::ATOM));
    }

    /**
     * Legacy compatibility for old non UTC ATOM timestamps {@see https://github.com/neos/neos-development-collection/pull/5716}
     */
    #[Test]
    public function getInitiatingTimestampReturnsUtc(): void
    {
        $extracted = InitiatingEventMetadata::getInitiatingTimestamp(EventMetadata::fromArray([
            'initiatingTimestamp' => '2024-09-22T12:00:00+01:00'
        ]));
        self::assertEquals(0, $extracted->getOffset());
        self::assertEquals('2024-09-22T11:00:00+00:00', $extracted->format(\DateTimeImmutable::ATOM));
    }
}
