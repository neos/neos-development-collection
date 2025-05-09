<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\BehavioralTests\TestSuite\DebugEventProjection;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;
use Neos\Flow\Core\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Just a test to check our mock is working {@see DebugEventProjection}
 */
final class DebugEventProjectionTest extends TestCase
{
    private DebugEventProjection $debugEventProjection;

    public function setUp(): void
    {
        $this->debugEventProjection = new DebugEventProjection(
            'test_debug_projection',
            Bootstrap::$staticObjectManager->get(Connection::class)
        );

        $this->debugEventProjection->setUp();
    }

    public function tearDown(): void
    {
        $this->debugEventProjection->resetState();
    }

    /** @test */
    public function fakeProjectionRejectsDuplicateEvents()
    {
        $fakeEventEnvelope = $this->createExampleEventEnvelopeForPosition(
            SequenceNumber::fromInteger(1)
        );

        $this->debugEventProjection->apply(
            $this->getMockBuilder(EventInterface::class)->getMock(),
            $fakeEventEnvelope
        );

        $this->expectExceptionMessage('Must not happen! Debug projection detected duplicate event 1 of type ContentStreamWasCreated');

        $this->debugEventProjection->apply(
            $this->getMockBuilder(EventInterface::class)->getMock(),
            $fakeEventEnvelope
        );
    }

    /** @test */
    public function fakeProjectionWithSaboteur()
    {
        $fakeEventEnvelope1 = $this->createExampleEventEnvelopeForPosition(
            SequenceNumber::fromInteger(1)
        );

        $fakeEventEnvelope2 = $this->createExampleEventEnvelopeForPosition(
            SequenceNumber::fromInteger(2)
        );

        $this->debugEventProjection->injectSaboteur(
            fn (EventEnvelope $eventEnvelope) =>
                $eventEnvelope->sequenceNumber->value === 2
                    ? throw new \RuntimeException('sabotage!!!')
                    : null
        );

        // catchup
        $this->debugEventProjection->apply(
            $this->getMockBuilder(EventInterface::class)->getMock(),
            $fakeEventEnvelope1
        );

        $this->expectExceptionMessage('sabotage!!!');

        $this->debugEventProjection->apply(
            $this->getMockBuilder(EventInterface::class)->getMock(),
            $fakeEventEnvelope2
        );
    }

    private function createExampleEventEnvelopeForPosition(SequenceNumber $sequenceNumber): EventEnvelope
    {
        $cs = ContentStreamId::create();
        return new EventEnvelope(
            new Event(
                Event\EventId::create(),
                Event\EventType::fromString('ContentStreamWasCreated'),
                Event\EventData::fromString(json_encode(['contentStreamId' => $cs->value]))
            ),
            ContentStreamEventStreamName::fromContentStreamId($cs)->getEventStreamName(),
            Event\Version::first(),
            $sequenceNumber,
            new \DateTimeImmutable()
        );
    }
}
