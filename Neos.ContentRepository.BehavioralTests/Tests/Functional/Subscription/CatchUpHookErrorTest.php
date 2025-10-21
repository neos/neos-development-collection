<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription;

use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookFailed;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Subscription\Engine\Error;
use Neos\ContentRepository\Core\Subscription\Engine\Errors;
use Neos\ContentRepository\Core\Subscription\Engine\ProcessedResult;
use Neos\ContentRepository\Core\Subscription\ProjectionSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionError;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;

final class CatchUpHookErrorTest extends AbstractSubscriptionEngineTestCase
{
    /** @test */
    public function error_onBeforeEvent_isIgnoredAndCollected()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::exactly(2))->method('apply');
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();

        // commit two events. we expect that the hook will throw for both events but the catchup is NOT halted
        $this->commitExampleContentStreamEvent();
        $this->commitExampleContentStreamEvent();

        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::ACTIVE);

        $exception = new \RuntimeException('This catchup hook is kaputt.');
        $this->catchupHookForSecondFakeProjection->expects($invokedCount = self::exactly(2))->method('onBeforeEvent')->willReturnCallback(function ($_, EventEnvelope $eventEnvelope) use ($invokedCount, $exception) {
            match ($invokedCount->getInvocationCount()) {
                1 => self::assertSame(1, $eventEnvelope->sequenceNumber->value),
                2 => self::assertSame(2, $eventEnvelope->sequenceNumber->value),
            };
            throw $exception;
        });
        $this->catchupHookForSecondFakeProjection->expects(self::exactly(2))->method('onAfterEvent');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterBatchCompleted');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterCatchUp');

        self::assertEmpty(
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        $expectedWrappedException = new CatchUpHookFailed(
            'Hook "onBeforeEvent" failed: "": This catchup hook is kaputt.',
            1733243960,
            $exception
        );

        // two errors for both of the events
        $result = $this->subscriptionEngine->catchUpActive();
        self::assertEquals(
            ProcessedResult::failed(
                2,
                Errors::fromArray([
                    Error::create(
                        SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                        $expectedWrappedException->getMessage(),
                        $expectedWrappedException,
                        SequenceNumber::fromInteger(1)
                    ),
                    Error::create(
                        SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                        $expectedWrappedException->getMessage(),
                        null,
                        SequenceNumber::fromInteger(2)
                    ),
                ])
            ),
            $result
        );

        // both events are applied still
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
        self::assertEquals(
            [SequenceNumber::fromInteger(1), SequenceNumber::fromInteger(2)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );
    }

    /** @test */
    public function error_onAfterEvent_isIgnoredAndCollected()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::exactly(2))->method('apply');
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();

        // commit two events. we expect that the hook will throw for both events but the catchup is NOT halted
        $this->commitExampleContentStreamEvent();
        $this->commitExampleContentStreamEvent();

        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::ACTIVE);
        $this->catchupHookForSecondFakeProjection->expects(self::exactly(2))->method('onBeforeEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class));
        $exception = new \RuntimeException('This catchup hook is kaputt.');
        $this->catchupHookForSecondFakeProjection->expects($invokedCount = self::exactly(2))->method('onAfterEvent')->willReturnCallback(function ($_, EventEnvelope $eventEnvelope) use ($invokedCount, $exception) {
            match ($invokedCount->getInvocationCount()) {
                1 => self::assertSame(1, $eventEnvelope->sequenceNumber->value),
                2 => self::assertSame(2, $eventEnvelope->sequenceNumber->value),
            };
            throw $exception;
        });
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterBatchCompleted');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterCatchUp'); // todo assert no parameters?!

        self::assertEmpty(
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        $expectedWrappedException = new CatchUpHookFailed(
            'Hook "onAfterEvent" failed: "": This catchup hook is kaputt.',
            1733243960,
            $exception
        );

        // two errors for both of the events
        $result = $this->subscriptionEngine->catchUpActive();
        self::assertEquals(
            ProcessedResult::failed(
                2,
                Errors::fromArray([
                    Error::create(
                        SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                        $expectedWrappedException->getMessage(),
                        $expectedWrappedException,
                        SequenceNumber::fromInteger(1)
                    ),
                    Error::create(
                        SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                        $expectedWrappedException->getMessage(),
                        null,
                        SequenceNumber::fromInteger(2)
                    ),
                ])
            ),
            $result
        );

        // both events are applied still
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
        self::assertEquals(
            [SequenceNumber::fromInteger(1), SequenceNumber::fromInteger(2)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );
    }

    /** @test */
    public function error_onBeforeCatchUp_isIgnoredAndCollected()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::exactly(2))->method('apply');
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();

        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::none());

        // commit two events. we expect that the hook will throw for both events but the catchup is NOT halted
        $this->commitExampleContentStreamEvent();
        $this->commitExampleContentStreamEvent();

        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::ACTIVE)->willThrowException(
            $exception = new \RuntimeException('This catchup hook is kaputt.')
        );
        $this->catchupHookForSecondFakeProjection->expects(self::exactly(2))->method('onBeforeEvent');
        $this->catchupHookForSecondFakeProjection->expects(self::exactly(2))->method('onAfterEvent');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterBatchCompleted');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterCatchUp');

        self::assertEmpty(
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        $expectedWrappedException = new CatchUpHookFailed(
            'Hook "onBeforeCatchUp" failed: "": This catchup hook is kaputt.',
            1733243960,
            $exception
        );

        $result = $this->subscriptionEngine->catchUpActive();
        self::assertEquals(
            ProcessedResult::failed(
                2,
                Errors::fromArray([
                    Error::create(
                        SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                        $expectedWrappedException->getMessage(),
                        $expectedWrappedException,
                        null
                    ),
                ])
            ),
            $result
        );

        // both events are applied still
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
        self::assertEquals(
            [SequenceNumber::fromInteger(1), SequenceNumber::fromInteger(2)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );
    }

    /** @test */
    public function error_onAfterBatchCompleted_isIgnoredAndCollected()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::exactly(2))->method('apply');
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();

        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::none());

        // commit two events. we expect that the hook will throw for both events but the catchup is NOT halted
        $this->commitExampleContentStreamEvent();
        $this->commitExampleContentStreamEvent();

        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp');
        $this->catchupHookForSecondFakeProjection->expects(self::exactly(2))->method('onBeforeEvent');
        $this->catchupHookForSecondFakeProjection->expects(self::exactly(2))->method('onAfterEvent');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterBatchCompleted')->willThrowException(
            $exception = new \RuntimeException('This catchup hook is kaputt.')
        );
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterCatchUp');

        self::assertEmpty(
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        $expectedWrappedException = new CatchUpHookFailed(
            'Hook "onAfterBatchCompleted" failed: "": This catchup hook is kaputt.',
            1733243960,
            $exception
        );

        $result = $this->subscriptionEngine->catchUpActive();
        self::assertEquals(
            ProcessedResult::failed(
                2,
                Errors::fromArray([
                    Error::create(
                        SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                        $expectedWrappedException->getMessage(),
                        $expectedWrappedException,
                        null
                    ),
                ])
            ),
            $result
        );

        // both events are applied still
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
        self::assertEquals(
            [SequenceNumber::fromInteger(1), SequenceNumber::fromInteger(2)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );
    }

    /** @test */
    public function error_onAfterCatchUp_isIgnoredAndCollected()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::exactly(2))->method('apply');
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();

        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::none());

        // commit two events. we expect that the hook will throw for both events but the catchup is NOT halted
        $this->commitExampleContentStreamEvent();
        $this->commitExampleContentStreamEvent();

        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp');
        $this->catchupHookForSecondFakeProjection->expects(self::exactly(2))->method('onBeforeEvent');
        $this->catchupHookForSecondFakeProjection->expects(self::exactly(2))->method('onAfterEvent');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterBatchCompleted');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterCatchUp')->willThrowException(
            $exception = new \RuntimeException('This catchup hook is kaputt.')
        );

        self::assertEmpty(
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        $expectedWrappedException = new CatchUpHookFailed(
            'Hook "onAfterCatchUp" failed: "": This catchup hook is kaputt.',
            1733243960,
            $exception
        );

        $result = $this->subscriptionEngine->catchUpActive();
        self::assertEquals(
            ProcessedResult::failed(
                2,
                Errors::fromArray([
                    Error::create(
                        SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                        $expectedWrappedException->getMessage(),
                        $expectedWrappedException,
                        null
                    ),
                ])
            ),
            $result
        );

        // both events are applied still
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
        self::assertEquals(
            [SequenceNumber::fromInteger(1), SequenceNumber::fromInteger(2)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );
    }

    /** @test */
    public function error_onAfterCatchUp_isIgnoredAndCollected_withProjectionError()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::exactly(2))->method('apply');
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();

        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::none());

        // commit two events. we expect that the hook will throw for both events but the catchup is NOT halted
        $this->commitExampleContentStreamEvent();
        $this->commitExampleContentStreamEvent();

        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp');
        // only the onBeforeEvent hook will be invoked as afterward the projection errored
        $this->catchupHookForSecondFakeProjection->expects(self::exactly(1))->method('onBeforeEvent');
        $this->catchupHookForSecondFakeProjection->expects(self::never())->method('onAfterEvent');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterBatchCompleted');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterCatchUp')->willThrowException(
            $exception = new \RuntimeException('This catchup hook is kaputt.')
        );

        $innerException = new \RuntimeException('Projection is kaputt.');
        $this->secondFakeProjection->injectSaboteur(fn () => throw $innerException);

        self::assertEmpty(
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        $expectedWrappedException = new CatchUpHookFailed(
            'Hook "onAfterCatchUp" failed: "": This catchup hook is kaputt.',
            1733243960,
            $exception
        );

        // two errors for both of the events
        $result = $this->subscriptionEngine->catchUpActive();

        self::assertEquals(
            ProcessedResult::failed(
                2,
                Errors::fromArray([
                    Error::create(
                        SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                        $innerException->getMessage(),
                        $innerException,
                        SequenceNumber::fromInteger(1)
                    ),
                    Error::create(
                        SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                        $expectedWrappedException->getMessage(),
                        null,
                        null
                    ),
                ])
            ),
            $result
        );

        $expectedFailure = ProjectionSubscriptionStatus::create(
            subscriptionId: SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
            subscriptionStatus: SubscriptionStatus::ERROR,
            subscriptionPosition: SequenceNumber::none(),
            subscriptionError: SubscriptionError::fromPreviousStatusAndException(SubscriptionStatus::ACTIVE, $innerException),
            setupStatus: ProjectionStatus::ok(),
        );

        // projection is marked as error
        self::assertEquals(
            $expectedFailure,
            $this->subscriptionStatus('Vendor.Package:SecondFakeProjection')
        );
        // partially applied event because the error is thrown at the end and the projection is not rolled back
        self::assertEquals(
            [1],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues()
        );
    }

    /** @test */
    public function error_onAfterEvent_stopsEngineAfterFirstBatch()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::once())->method('apply');
        $this->subscriptionEngine->setup();

        // commit two events. we expect that the hook will throw the first event and due to the batching its halted
        $this->commitExampleContentStreamEvent();
        $this->commitExampleContentStreamEvent();

        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::BOOTING);
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class));
        $exception = new \RuntimeException('This catchup hook is kaputt.');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterEvent')->willThrowException(
            $exception
        );
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterBatchCompleted');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterCatchUp');

        self::assertEmpty(
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::fromInteger(0));

        $expectedWrappedException = new CatchUpHookFailed(
            'Hook "onAfterEvent" failed: "": This catchup hook is kaputt.',
            1733243960,
            $exception
        );

        // one error
        $result = $this->subscriptionEngine->boot(batchSize: 1);
        self::assertEquals(
            ProcessedResult::failed(
                1,
                Errors::fromArray([
                    Error::create(
                        SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                        $expectedWrappedException->getMessage(),
                        $expectedWrappedException,
                        SequenceNumber::fromInteger(1)
                    ),
                ])
            ),
            $result
        );

        // only one event is applied
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::fromInteger(1));
        self::assertEquals(
            [SequenceNumber::fromInteger(1)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );
    }

    /** @test */
    public function error_onAfterEvent_withMultipleFailingHooks()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());
        $this->fakeProjection->expects(self::once())->method('apply');
        $this->subscriptionEngine->setup();

        $this->commitExampleContentStreamEvent();

        $this->catchupHookForFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::BOOTING);
        $this->catchupHookForFakeProjection->expects(self::once())->method('onBeforeEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class));
        $firstException = new \RuntimeException('First catchup hook is kaputt.');
        $this->catchupHookForFakeProjection->expects(self::once())->method('onAfterEvent')->willThrowException(
            $firstException
        );
        $this->catchupHookForFakeProjection->expects(self::once())->method('onAfterBatchCompleted');
        $this->catchupHookForFakeProjection->expects(self::once())->method('onAfterCatchUp');

        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::BOOTING);
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class));
        $secondException = new \RuntimeException('Second catchup hook is kaputt.');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterEvent')->willThrowException(
            $secondException
        );
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterBatchCompleted');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterCatchUp');

        self::assertEmpty(
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::fromInteger(0));
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::fromInteger(0));

        $result = $this->subscriptionEngine->boot();
        self::assertEquals(
            ProcessedResult::failed(
                1,
                Errors::fromArray([
                    Error::create(
                        SubscriptionId::fromString('Vendor.Package:FakeProjection'),
                        'Hook "onAfterEvent" failed: "": First catchup hook is kaputt.',
                        new CatchUpHookFailed(
                            'Hook "onAfterEvent" failed: "": First catchup hook is kaputt.',
                            1733243960,
                            $firstException
                        ),
                        SequenceNumber::fromInteger(1)
                    ),
                    Error::create(
                        SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                        'Hook "onAfterEvent" failed: "": Second catchup hook is kaputt.',
                        null,
                        SequenceNumber::fromInteger(1)
                    ),
                ])
            ),
            $result
        );

        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));
        self::assertEquals(
            [SequenceNumber::fromInteger(1)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );
    }

    /** @test */
    public function error_onAfterEvent_withMultipleFailingHooksOnOneProjection()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());
        $this->fakeProjection->expects(self::once())->method('apply');
        $this->subscriptionEngine->setup();

        $this->commitExampleContentStreamEvent();

        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::BOOTING);
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class));
        $firstException = new \RuntimeException('First catchup hook is kaputt.');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterEvent')->willThrowException(
            $firstException
        );
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterBatchCompleted');
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterCatchUp');

        $this->additionalCatchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::BOOTING);
        $this->additionalCatchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class));
        $secondException = new \RuntimeException('Second catchup hook is kaputt.');
        $this->additionalCatchupHookForSecondFakeProjection->expects(self::once())->method('onAfterEvent')->willThrowException(
            $secondException
        );
        $this->additionalCatchupHookForSecondFakeProjection->expects(self::once())->method('onAfterBatchCompleted');
        $this->additionalCatchupHookForSecondFakeProjection->expects(self::once())->method('onAfterCatchUp');

        self::assertEmpty(
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::fromInteger(0));
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::fromInteger(0));

        $result = $this->subscriptionEngine->boot();
        self::assertEquals(
            ProcessedResult::failed(
                1,
                Errors::fromArray([
                    Error::create(
                        SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                        $message = 'Hook "onAfterEvent" failed: "": First catchup hook is kaputt.;' . PHP_EOL .
                            '"": Second catchup hook is kaputt.',
                        new CatchUpHookFailed(
                            $message,
                            1733243960,
                            $firstException
                        ),
                        SequenceNumber::fromInteger(1)
                    ),
                ])
            ),
            $result
        );

        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));
        self::assertEquals(
            [SequenceNumber::fromInteger(1)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );
    }
}
