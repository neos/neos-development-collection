<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription;

use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Subscription\Engine\ProcessedResult;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngineCriteria;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\Event\SequenceNumber;

final class SubscriptionBootingStatusTest extends AbstractSubscriptionEngineTestCase
{

    /** @test */
    public function existingEventStoreEventsAreCaughtUpOnBoot()
    {
        $this->eventStore->setup();
        // commit an event
        $this->commitExampleContentStreamEvent();

        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->subscriptionEngine->setup();
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());

        $this->expectOkayStatus('contentGraph', SubscriptionStatus::BOOTING, SequenceNumber::none());
        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::none());

        $this->fakeProjection->expects(self::once())->method('apply')->with(self::isInstanceOf(ContentStreamWasCreated::class));
        $this->subscriptionEngine->boot();

        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));
        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));

        // catchup is a noop because there are no unhandled events
        $result = $this->subscriptionEngine->catchUpActive();
        self::assertEquals(ProcessedResult::success(0), $result);
    }

    /** @test */
    public function filteringCatchUpBoot()
    {
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());

        $this->eventStore->setup();

        $result = $this->subscriptionEngine->setup();
        self::assertNull($result->errors);


        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::none());
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::none());

        $filter = SubscriptionEngineCriteria::create([SubscriptionId::fromString('Vendor.Package:FakeProjection')]);

        $result = $this->subscriptionEngine->boot($filter);
        self::assertNull($result->errors);

        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::none());
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::none());
    }
}
