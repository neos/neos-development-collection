<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.ContentRepository.Core package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\ContentRepository\Core\Tests\Unit\EventStore;

use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\EventStore\Model\Event\CausationId;
use Neos\EventStore\Model\Event\CorrelationId;
use Neos\EventStore\Model\Event\EventId;
use Neos\EventStore\Model\Event\EventMetadata;
use PHPUnit\Framework\TestCase;

final class DecoratedEventTest extends TestCase
{
    private EventInterface $mockEvent;

    public function setUp(): void
    {
        $this->mockEvent = $this->getMockBuilder(EventInterface::class)->getMock();
    }

    /**
     * @test
     */
    public function createMergesDataOfDecoratedEvent(): void
    {
        $decoratedEvent = DecoratedEvent::create($this->mockEvent, eventId: EventId::fromString('65f16ed8-7ffa-432d-b7bf-4ccd4b22c294'));
        $decoratedEvent = DecoratedEvent::create($decoratedEvent, causationId: CausationId::fromString('some-causation-id'));
        $decoratedEvent = DecoratedEvent::create($decoratedEvent, correlationId: CorrelationId::fromString('some-correlation-id'));
        $decoratedEvent = DecoratedEvent::create($decoratedEvent, metadata: EventMetadata::fromArray(['foo' => 'bar']));
        self::assertSame('65f16ed8-7ffa-432d-b7bf-4ccd4b22c294', $decoratedEvent->eventId?->value);
        self::assertSame('some-causation-id', $decoratedEvent->causationId?->value);
        self::assertSame('some-correlation-id', $decoratedEvent->correlationId?->value);
        self::assertSame(['foo' => 'bar'], $decoratedEvent->eventMetadata?->value);
    }


}
