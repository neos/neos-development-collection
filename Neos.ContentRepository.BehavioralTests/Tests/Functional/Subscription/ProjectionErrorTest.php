<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription;

use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\Subscription\Engine\Error;
use Neos\ContentRepository\Core\Subscription\Engine\Errors;
use Neos\ContentRepository\Core\Subscription\Engine\ProcessedResult;
use Neos\ContentRepository\Core\Subscription\Engine\Result;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngineCriteria;
use Neos\ContentRepository\Core\Subscription\Exception\CatchUpHadErrors;
use Neos\ContentRepository\Core\Subscription\ProjectionSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionError;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;

final class ProjectionErrorTest extends AbstractSubscriptionEngineTestCase
{
    /** @test */
    public function projectionWithErrorCanBeReactivated()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->subscriptionEngine->setup();
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());
        $result = $this->subscriptionEngine->boot();
        self::assertEquals(ProcessedResult::success(0), $result);
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::none());
        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::none());

        // commit an event
        $this->commitExampleContentStreamEvent();

        // catchup active tries to apply the commited event
        $exception = new \RuntimeException('This projection is kaputt.');
        $this->fakeProjection->expects($i = self::exactly(2))->method('apply')->willReturnCallback(function ($_, EventEnvelope $eventEnvelope) use ($i, $exception) {
            match($i->getInvocationCount()) {
                1 => [
                    self::assertEquals(1, $eventEnvelope->sequenceNumber->value),
                    throw $exception
                ],
                2 => [
                    // on second call is repaired:
                    self::assertEquals(1, $eventEnvelope->sequenceNumber->value),
                ]
            };
        });
        $expectedStatusForFailedProjection = ProjectionSubscriptionStatus::create(
            subscriptionId: SubscriptionId::fromString('Vendor.Package:FakeProjection'),
            subscriptionStatus: SubscriptionStatus::ERROR,
            subscriptionPosition: SequenceNumber::none(),
            subscriptionError: SubscriptionError::fromPreviousStatusAndException(SubscriptionStatus::ACTIVE, $exception),
            setupStatus: ProjectionStatus::ok(),
        );

        $result = $this->subscriptionEngine->catchUpActive();
        self::assertEquals(ProcessedResult::failed(1, Errors::fromArray([Error::create(
            SubscriptionId::fromString('Vendor.Package:FakeProjection'),
            $exception->getMessage(),
            $exception,
            SequenceNumber::fromInteger(1)
        )])), $result);

        self::assertEquals(
            $expectedStatusForFailedProjection,
            $this->subscriptionStatus('Vendor.Package:FakeProjection')
        );
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));

        //
        // fix projection and catchup
        //

        // reactivate and catchup
        $result = $this->subscriptionEngine->reactivate(SubscriptionEngineCriteria::create([SubscriptionId::fromString('Vendor.Package:FakeProjection')]));
        self::assertNull($result->errors);

        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));
    }

    /** @test */
    public function fixFailedProjectionViaReset()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());
        $this->fakeProjection->expects(self::once())->method('resetState');
        $this->fakeProjection->expects(self::exactly(2))->method('apply');
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();

        // commit an event
        $this->commitExampleContentStreamEvent();

        // catchup active tries to apply the commited event
        $exception = new \RuntimeException('This projection is kaputt.');
        $this->secondFakeProjection->injectSaboteur(function (EventEnvelope $eventEnvelope) use ($exception) {
            self::assertEquals(SequenceNumber::fromInteger(1), $eventEnvelope->sequenceNumber);
            self::assertEquals('ContentStreamWasCreated', $eventEnvelope->event->type->value);
            throw $exception;
        });

        $expectedFailure = ProjectionSubscriptionStatus::create(
            subscriptionId: SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
            subscriptionStatus: SubscriptionStatus::ERROR,
            subscriptionPosition: SequenceNumber::none(),
            subscriptionError: SubscriptionError::fromPreviousStatusAndException(SubscriptionStatus::ACTIVE, $exception),
            setupStatus: ProjectionStatus::ok(),
        );

        $result = $this->subscriptionEngine->catchUpActive();
        self::assertTrue($result->hadErrors());

        self::assertEquals(
            $expectedFailure,
            $this->subscriptionStatus('Vendor.Package:SecondFakeProjection')
        );

        $this->secondFakeProjection->killSaboteur();

        $result = $this->subscriptionEngine->reset();
        self::assertNull($result->errors);

        // expect the subscriptionError to be reset to null
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::none());

        $result = $this->subscriptionEngine->boot();
        self::assertNull($result->errors);

        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));
    }

    /** @test */
    public function irreparableProjection()
    {
        // test ways NOT to fix a projection :)
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::exactly(2))->method('setUp');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());
        $this->fakeProjection->expects(self::exactly(2))->method('apply');
        $this->fakeProjection->expects(self::once())->method('resetState');
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();

        // commit an event
        $this->commitExampleContentStreamEvent();

        $exception = new \RuntimeException('This projection is kaputt.');
        $this->secondFakeProjection->injectSaboteur(function (EventEnvelope $eventEnvelope) use ($exception) {
            self::assertEquals(SequenceNumber::fromInteger(1), $eventEnvelope->sequenceNumber);
            self::assertEquals('ContentStreamWasCreated', $eventEnvelope->event->type->value);
            throw $exception;
        });

        $expectedFailure = ProjectionSubscriptionStatus::create(
            subscriptionId: SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
            subscriptionStatus: SubscriptionStatus::ERROR,
            subscriptionPosition: SequenceNumber::none(),
            subscriptionError: SubscriptionError::fromPreviousStatusAndException(SubscriptionStatus::ACTIVE, $exception),
            setupStatus: ProjectionStatus::ok(),
        );

        // catchup active tries to apply the commited event
        $result = $this->subscriptionEngine->catchUpActive();
        // but fails
        self::assertTrue($result->hadErrors());
        self::assertEquals($expectedFailure, $this->subscriptionStatus('Vendor.Package:SecondFakeProjection'));

        // a second catchup active does not change anything
        $result = $this->subscriptionEngine->catchUpActive();
        self::assertEquals(ProcessedResult::success(0), $result);
        self::assertEquals($expectedFailure, $this->subscriptionStatus('Vendor.Package:SecondFakeProjection'));

        // boot neither
        $result = $this->subscriptionEngine->boot();
        self::assertEquals(ProcessedResult::success(0), $result);
        self::assertEquals($expectedFailure, $this->subscriptionStatus('Vendor.Package:SecondFakeProjection'));

        // setup neither
        $result = $this->subscriptionEngine->setup();
        self::assertEquals(Result::success(), $result);
        self::assertEquals($expectedFailure, $this->subscriptionStatus('Vendor.Package:SecondFakeProjection'));

        // reactivation will attempt to retry fix this, but can only work if the projection is repaired and will lead to an error otherwise:
        $result = $this->subscriptionEngine->reactivate();
        self::assertEquals(1, $result->numberOfProcessedEvents);
        self::assertEquals('Must not happen! Debug projection detected duplicate event 1 of type ContentStreamWasCreated', $result->errors->first()?->message);

        self::assertEquals(
            ProjectionSubscriptionStatus::create(
                subscriptionId: SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                subscriptionStatus: SubscriptionStatus::ERROR,
                subscriptionPosition: SequenceNumber::none(),
                // previous state is now an error too also error:
                subscriptionError: SubscriptionError::fromPreviousStatusAndException(SubscriptionStatus::ERROR, $result->errors->first()->throwable),
                setupStatus: ProjectionStatus::ok(),
            ),
            $this->subscriptionStatus('Vendor.Package:SecondFakeProjection')
        );

        // expect the subscriptionError to be reset to null
        $result = $this->subscriptionEngine->reset();
        self::assertNull($result->errors);
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::none());

        // but booting will rethrow that error :D
        $result = $this->subscriptionEngine->boot();
        self::assertTrue($result->hadErrors());
        self::assertEquals(
            ProjectionSubscriptionStatus::create(
                subscriptionId: SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                subscriptionStatus: SubscriptionStatus::ERROR,
                subscriptionPosition: SequenceNumber::none(),
                // previous state is now booting
                subscriptionError: SubscriptionError::fromPreviousStatusAndException(SubscriptionStatus::BOOTING, $exception),
                setupStatus: ProjectionStatus::ok(),
            ),
            $this->subscriptionStatus('Vendor.Package:SecondFakeProjection')
        );
    }

    /** @test */
    public function projectionWithError()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::once())->method('apply');
        $result = $this->subscriptionEngine->setup();
        self::assertNull($result->errors);
        $result = $this->subscriptionEngine->boot();
        self::assertNull($result->errors);

        // commit an event
        $this->commitExampleContentStreamEvent();

        $exception = new \RuntimeException('This projection is kaputt.');

        $this->secondFakeProjection->injectSaboteur(fn () => throw $exception);

        $expectedFailure = ProjectionSubscriptionStatus::create(
            subscriptionId: SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
            subscriptionStatus: SubscriptionStatus::ERROR,
            subscriptionPosition: SequenceNumber::none(),
            subscriptionError: SubscriptionError::fromPreviousStatusAndException(SubscriptionStatus::ACTIVE, $exception),
            setupStatus: ProjectionStatus::ok(),
        );

        self::assertEmpty(
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        $result = $this->subscriptionEngine->catchUpActive();
        self::assertSame($result->errors?->first()->message, 'This projection is kaputt.');

        self::assertEquals(
            $expectedFailure,
            $this->subscriptionStatus('Vendor.Package:SecondFakeProjection')
        );

        // because the error is thrown after the even the state is commited
        self::assertEquals(
            [1],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues()
        );
    }

    /** @test */
    public function projectionWithErrorAfterSecondEvent()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::exactly(2))->method('apply');
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();

        // commit two events
        $this->commitExampleContentStreamEvent();
        $this->commitExampleContentStreamEvent();

        $exception = new \RuntimeException('Event 2 is kaputt.');

        // fail at the second event
        $this->secondFakeProjection->injectSaboteur(
            fn (EventEnvelope $eventEnvelope) =>
            $eventEnvelope->sequenceNumber->value === 2
                ? throw $exception
                : null
        );

        self::assertEmpty(
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        $result = $this->subscriptionEngine->catchUpActive();
        self::assertTrue($result->hadErrors());

        $expectedFailure = ProjectionSubscriptionStatus::create(
            subscriptionId: SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
            subscriptionStatus: SubscriptionStatus::ERROR,
            subscriptionPosition: SequenceNumber::fromInteger(1),
            subscriptionError: SubscriptionError::fromPreviousStatusAndException(SubscriptionStatus::ACTIVE, $exception),
            setupStatus: ProjectionStatus::ok(),
        );

        self::assertEquals(
            $expectedFailure,
            $this->subscriptionStatus('Vendor.Package:SecondFakeProjection')
        );

        // the first successful event is applied and committet, but the second partially applied event is also applied:
        self::assertEquals(
            [SequenceNumber::fromInteger(1), SequenceNumber::fromInteger(2)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );
    }

    /** @test */
    public function projectionErrorWithMultipleProjectionsInContentRepositoryHandle()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();

        $this->fakeProjection->expects(self::once())->method('apply')->with(self::isInstanceOf(ContentStreamWasCreated::class))->willThrowException(
            $originalException = new \RuntimeException('This projection is kaputt.'),
        );

        $handleException = null;
        try {
            $this->contentRepository->handle(CreateRootWorkspace::create(WorkspaceName::fromString('root'), ContentStreamId::fromString('root-cs')));
        } catch (\RuntimeException $exception) {
            $handleException = $exception;
        }
        self::assertInstanceOf(CatchUpHadErrors::class, $exception);
        self::assertEquals('Error while catching up: Event 1 in "Vendor.Package:FakeProjection": This projection is kaputt.', $handleException->getMessage());
        self::assertSame($originalException, $handleException->getPrevious());

        // workspace is created. The fake projection failed on the first event, but other projections succeed:
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
        self::assertNotNull($this->contentRepository->findWorkspaceByName(WorkspaceName::fromString('root')));
        self::assertEquals(
            [SequenceNumber::fromInteger(1), SequenceNumber::fromInteger(2)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        // to exception thrown here because the failed projection is not retried and now in error state
        $this->contentRepository->handle(CreateRootWorkspace::create(WorkspaceName::fromString('root-two'), ContentStreamId::fromString('root-cs-two')));

        // workspace two is created.
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(4));
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(4));
        self::assertNotNull($this->contentRepository->findWorkspaceByName(WorkspaceName::fromString('root-two')));
        self::assertEquals(
            [SequenceNumber::fromInteger(1), SequenceNumber::fromInteger(2), SequenceNumber::fromInteger(3), SequenceNumber::fromInteger(4)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );
    }

    /** @test */
    public function projectionError_stopsEngineAfterFirstBatch()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());
        $this->subscriptionEngine->setup();
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::BOOTING, SequenceNumber::none());
        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::none());

        // commit two events
        $this->commitExampleContentStreamEvent();
        $this->commitExampleContentStreamEvent();

        $this->fakeProjection->expects(self::once())->method('apply')->with(self::isInstanceOf(ContentStreamWasCreated::class))->willThrowException(
            $exception = new \RuntimeException('This projection is kaputt.')
        );
        $expectedStatusForFailedProjection = ProjectionSubscriptionStatus::create(
            subscriptionId: SubscriptionId::fromString('Vendor.Package:FakeProjection'),
            subscriptionStatus: SubscriptionStatus::ERROR,
            subscriptionPosition: SequenceNumber::none(),
            subscriptionError: SubscriptionError::fromPreviousStatusAndException(SubscriptionStatus::BOOTING, $exception),
            setupStatus: ProjectionStatus::ok(),
        );

        $result = $this->subscriptionEngine->boot(batchSize: 1);
        self::assertEquals(ProcessedResult::failed(1, Errors::fromArray([Error::create(
            SubscriptionId::fromString('Vendor.Package:FakeProjection'),
            $exception->getMessage(),
            $exception,
            SequenceNumber::fromInteger(1)
        )])), $result);

        self::assertEquals(
            $expectedStatusForFailedProjection,
            $this->subscriptionStatus('Vendor.Package:FakeProjection')
        );
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::BOOTING, SequenceNumber::fromInteger(1));
    }
}
