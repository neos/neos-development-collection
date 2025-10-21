<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngineCriteria;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;

final class CatchUpHookTest extends AbstractSubscriptionEngineTestCase
{
    /** @test */
    public function catchUpHooksAreExecutedAndCanAccessTheCorrectProjectionsState()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::once())->method('apply');
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();

        // commit an event
        $this->commitExampleContentStreamEvent();

        $expectNoHandledEvents = fn () => self::assertEmpty(
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        $expectOneHandledEvent = fn () => self::assertEquals(
            [
                SequenceNumber::fromInteger(1)
            ],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        $expectNoTransactionActive = fn () => self::assertFalse(
            $this->getObject(Connection::class)->isTransactionActive(), 'Expected no transaction to be active'
        );

        $expectTransactionActive = fn () => self::assertTrue(
            $this->getObject(Connection::class)->isTransactionActive(), 'Expected transaction to be active'
        );

        // first projection hooks
        $this->catchupHookForFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::ACTIVE)->willReturnCallback($expectTransactionActive);
        $this->catchupHookForFakeProjection->expects(self::once())->method('onBeforeEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class))->willReturnCallback($expectTransactionActive);
        $this->catchupHookForFakeProjection->expects(self::once())->method('onAfterEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class))->willReturnCallback($expectTransactionActive);
        $this->catchupHookForFakeProjection->expects(self::once())->method('onAfterBatchCompleted')->willReturnCallback($expectNoTransactionActive);
        $this->catchupHookForFakeProjection->expects(self::once())->method('onAfterCatchUp')->willReturnCallback($expectNoTransactionActive);

        // second projection hooks
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::ACTIVE)->willReturnCallback($expectNoHandledEvents);
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class))->willReturnCallback($expectNoHandledEvents);
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class))->willReturnCallback($expectOneHandledEvent);
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterBatchCompleted')->willReturnCallback($expectOneHandledEvent);
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterCatchUp')->willReturnCallback($expectOneHandledEvent);

        $this->additionalCatchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::ACTIVE)->willReturnCallback($expectNoHandledEvents);
        $this->additionalCatchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class))->willReturnCallback($expectNoHandledEvents);
        $this->additionalCatchupHookForSecondFakeProjection->expects(self::once())->method('onAfterEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class))->willReturnCallback($expectOneHandledEvent);
        $this->additionalCatchupHookForSecondFakeProjection->expects(self::once())->method('onAfterBatchCompleted')->willReturnCallback($expectOneHandledEvent);
        $this->additionalCatchupHookForSecondFakeProjection->expects(self::once())->method('onAfterCatchUp')->willReturnCallback($expectOneHandledEvent);

        $expectNoHandledEvents();

        $result = $this->subscriptionEngine->catchUpActive();
        self::assertNull($result->errors);

        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));

        $expectOneHandledEvent();
    }

    /** @test */
    public function catchUpBeforeAndAfterCatchupAreRunForZeroEvents()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::never())->method('apply');
        $this->subscriptionEngine->setup();

        // first projection hooks
        $this->catchupHookForFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::BOOTING);
        $this->catchupHookForFakeProjection->expects(self::never())->method('onBeforeEvent');
        $this->catchupHookForFakeProjection->expects(self::never())->method('onAfterEvent');
        $this->catchupHookForFakeProjection->expects(self::once())->method('onAfterBatchCompleted');
        $this->catchupHookForFakeProjection->expects(self::once())->method('onAfterCatchUp');

        // second projection hooks
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::BOOTING);
        $this->catchupHookForSecondFakeProjection->expects(self::never())->method('onBeforeEvent');
        $this->catchupHookForSecondFakeProjection->expects(self::never())->method('onAfterEvent');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterBatchCompleted');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterCatchUp');

        $result = $this->subscriptionEngine->boot();
        self::assertNull($result->errors);

        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(0));
        self::assertEmpty($this->secondFakeProjection->getState()->findAppliedSequenceNumberValues());
    }

    /** @test */
    public function catchUpBeforeAndAfterCatchupAreNotRunIfNoSubscriberMatches()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::never())->method('apply');
        $this->subscriptionEngine->setup();

        // first projection hooks
        $this->catchupHookForFakeProjection->expects(self::never())->method('onBeforeCatchUp');
        $this->catchupHookForFakeProjection->expects(self::never())->method('onBeforeEvent');
        $this->catchupHookForFakeProjection->expects(self::never())->method('onAfterEvent');
        $this->catchupHookForFakeProjection->expects(self::never())->method('onAfterBatchCompleted');
        $this->catchupHookForFakeProjection->expects(self::never())->method('onAfterCatchUp');

        // second projection hooks
        $this->catchupHookForSecondFakeProjection->expects(self::never())->method('onBeforeCatchUp');
        $this->catchupHookForSecondFakeProjection->expects(self::never())->method('onBeforeEvent');
        $this->catchupHookForSecondFakeProjection->expects(self::never())->method('onAfterEvent');
        $this->catchupHookForSecondFakeProjection->expects(self::never())->method('onAfterBatchCompleted');
        $this->catchupHookForSecondFakeProjection->expects(self::never())->method('onAfterCatchUp');

        $result = $this->subscriptionEngine->catchUpActive();
        self::assertNull($result->errors);
        self::assertEquals(0, $result->numberOfProcessedEvents);

        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::fromInteger(0));
        self::assertEmpty($this->secondFakeProjection->getState()->findAppliedSequenceNumberValues());
    }

    /** @test */
    public function catchHooksAreOnlyRunForMatchingSubscriber()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::never())->method('apply');
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();

        // first projection hooks
        $this->catchupHookForFakeProjection->expects(self::never())->method('onBeforeCatchUp');
        $this->catchupHookForFakeProjection->expects(self::never())->method('onBeforeEvent');
        $this->catchupHookForFakeProjection->expects(self::never())->method('onAfterEvent');
        $this->catchupHookForFakeProjection->expects(self::never())->method('onAfterBatchCompleted');
        $this->catchupHookForFakeProjection->expects(self::never())->method('onAfterCatchUp');

        // second projection hooks
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::ACTIVE);
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class));
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class));
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterBatchCompleted');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterCatchUp');

        $this->additionalCatchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::ACTIVE);
        $this->additionalCatchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class));
        $this->additionalCatchupHookForSecondFakeProjection->expects(self::once())->method('onAfterEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class));
        $this->additionalCatchupHookForSecondFakeProjection->expects(self::once())->method('onAfterBatchCompleted');
        $this->additionalCatchupHookForSecondFakeProjection->expects(self::once())->method('onAfterCatchUp');

        // commit an event
        $this->commitExampleContentStreamEvent();

        $result = $this->subscriptionEngine->catchUpActive(SubscriptionEngineCriteria::create(
            [SubscriptionId::fromString('Vendor.Package:SecondFakeProjection')]
        ));
        self::assertNull($result->errors);
        self::assertEquals(1, $result->numberOfProcessedEvents);

        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));
    }

    public function provideValidBatchSizes(): iterable
    {
        yield 'none' => [
            'batchSize' => null,
            'onAfterBatchCompletedInvocations' => [
                [1,2,3,4]
            ],
        ];
        yield 'one' => [
            'batchSize' => 1,
            'onAfterBatchCompletedInvocations' =>  [
                [1],
                [1,2],
                [1,2,3],
                [1,2,3,4],
                [1,2,3,4],
            ],
        ];
        yield 'two' => [
            'batchSize' => 2,
            'onAfterBatchCompletedInvocations' => [
                [1,2],
                [1,2,3,4],
                [1,2,3,4],
            ],
        ];
        yield 'four' => [
            'batchSize' => 4,
            // we have two calls as the batch size exactly matches the events and we are running again to see if we handled everything.
            'onAfterBatchCompletedInvocations' => [
                [1,2,3,4],
                [1,2,3,4],
            ],
        ];
        yield 'ten' => [
            'batchSize' => 10,
            'onAfterBatchCompletedInvocations' => [
                [1,2,3,4],
            ],
        ];
    }

    /**
     * @dataProvider provideValidBatchSizes
     * @test
     */
    public function catchUpHooksWithBatching(int|null $batchSize, array $onAfterBatchCompletedInvocations)
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::exactly(4))->method('apply');
        $this->subscriptionEngine->setup();

        // commit events (will be batched in chunks of two)
        $this->commitExampleContentStreamEvent();
        $this->commitExampleContentStreamEvent();
        $this->commitExampleContentStreamEvent();
        $this->commitExampleContentStreamEvent();

        // first projection hooks
        $this->catchupHookForFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::BOOTING);
        $this->catchupHookForFakeProjection->expects(self::exactly(4))->method('onBeforeEvent');
        $this->catchupHookForFakeProjection->expects(self::exactly(4))->method('onAfterEvent');
        $this->catchupHookForFakeProjection->expects(self::exactly(\count($onAfterBatchCompletedInvocations)))->method('onAfterBatchCompleted');
        $this->catchupHookForFakeProjection->expects(self::once())->method('onAfterCatchUp');

        // second projection hooks
        foreach ([
            $this->catchupHookForSecondFakeProjection,
            $this->additionalCatchupHookForSecondFakeProjection,
        ] as $catchUpHookMock) {
            $catchUpHookMock->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::BOOTING);
            $catchUpHookMock->expects($i = self::exactly(4))->method('onBeforeEvent')->willReturnCallback(function ($_, EventEnvelope $eventEnvelope) use ($i) {
                match($i->getInvocationCount()) {
                    1 => [
                        self::assertEquals(1, $eventEnvelope->sequenceNumber->value),
                        self::assertEquals([], $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues())
                    ],
                    2 => [
                        self::assertEquals(2, $eventEnvelope->sequenceNumber->value),
                        self::assertEquals([1], $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues())
                    ],
                    3 => [
                        self::assertEquals(3, $eventEnvelope->sequenceNumber->value),
                        self::assertEquals([1,2], $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues())
                    ],
                    4 => [
                        self::assertEquals(4, $eventEnvelope->sequenceNumber->value),
                        self::assertEquals([1,2,3], $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues())
                    ],
                };
            });
            $catchUpHookMock->expects($i = self::exactly(4))->method('onAfterEvent')->willReturnCallback(function ($_, EventEnvelope $eventEnvelope) use ($i) {
                match($i->getInvocationCount()) {
                    1 => [
                        self::assertEquals(1, $eventEnvelope->sequenceNumber->value),
                        self::assertEquals([1], $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues())
                    ],
                    2 => [
                        self::assertEquals(2, $eventEnvelope->sequenceNumber->value),
                        self::assertEquals([1,2], $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues())
                    ],
                    3 => [
                        self::assertEquals(3, $eventEnvelope->sequenceNumber->value),
                        self::assertEquals([1,2,3], $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues())
                    ],
                    4 => [
                        self::assertEquals(4, $eventEnvelope->sequenceNumber->value),
                        self::assertEquals([1,2,3,4], $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues())
                    ],
                };
            });
            $catchUpHookMock->expects($i = self::exactly(\count($onAfterBatchCompletedInvocations)))->method('onAfterBatchCompleted')->willReturnCallback(function () use ($i, $onAfterBatchCompletedInvocations) {
                self::assertEquals($onAfterBatchCompletedInvocations[$i->getInvocationCount() - 1], $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues());
            });
            $catchUpHookMock->expects(self::once())->method('onAfterCatchUp');
        }

        self::assertEmpty($this->secondFakeProjection->getState()->findAppliedSequenceNumberValues());

        $result = $this->subscriptionEngine->boot(batchSize: $batchSize);
        self::assertNull($result->errors);

        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(4));
        self::assertEquals([1,2,3,4], $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues());
    }
}
