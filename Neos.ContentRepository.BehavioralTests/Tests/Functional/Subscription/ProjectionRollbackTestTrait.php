<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription;

use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngineCriteria;
use Neos\ContentRepository\Core\Subscription\ProjectionSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionError;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;

trait ProjectionRollbackTestTrait
{
    /** @test */
    public function projectionIsRolledBackAfterError()
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

        // should be empty as we need an exact once delivery
        self::assertEmpty(
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        //
        // fix projection and catchup
        //

        $this->secondFakeProjection->killSaboteur();

        // reactivate and catchup
        $result = $this->subscriptionEngine->reactivate(SubscriptionEngineCriteria::create([SubscriptionId::fromString('Vendor.Package:SecondFakeProjection')]));
        self::assertNull($result->errors);

        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));
        self::assertEquals(
            [SequenceNumber::fromInteger(1)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );
    }

    /** @test */
    public function projectionIsRolledBackAfterErrorButKeepsSuccessFullEvents()
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
        self::assertTrue($result->hasFailed());

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

        // the first successful event is applied and committet:
        self::assertEquals(
            [SequenceNumber::fromInteger(1)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        //
        // fix projection and catchup
        //

        $this->secondFakeProjection->killSaboteur();

        // catchup after fix
        $result = $this->subscriptionEngine->reactivate(SubscriptionEngineCriteria::create([SubscriptionId::fromString('Vendor.Package:SecondFakeProjection')]));
        self::assertNull($result->errors);

        // subscriptionError is reset, and the position is advanced if there were events
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
        self::assertEquals(
            [SequenceNumber::fromInteger(1), SequenceNumber::fromInteger(2)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );
    }
}
