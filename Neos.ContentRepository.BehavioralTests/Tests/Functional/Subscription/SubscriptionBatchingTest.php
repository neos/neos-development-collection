<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription;

use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Subscription\Engine\ProcessedResult;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\Event\SequenceNumber;

final class SubscriptionBatchingTest extends AbstractSubscriptionEngineTestCase
{
    /** @test */
    public function singleBatchSize()
    {
        $this->eventStore->setup();
        // commit three events
        $this->commitExampleContentStreamEvent();
        $this->commitExampleContentStreamEvent();

        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());
        $this->fakeProjection->expects(self::exactly(2))->method('apply');
        $this->subscriptionEngine->setup();

        $this->expectOkayStatus('contentGraph', SubscriptionStatus::BOOTING, SequenceNumber::none());
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::none());

        $result = $this->subscriptionEngine->boot(batchSize: 1);
        self::assertEquals(ProcessedResult::success(2), $result);

        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));

        self::assertEquals(
            [SequenceNumber::fromInteger(1), SequenceNumber::fromInteger(2)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );
    }

    /** @test */
    public function invalidBatchSizes()
    {
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->subscriptionEngine->setup();

        $e = null;
        try {
            $this->subscriptionEngine->boot(batchSize: 0);
        } catch (\Throwable $e) {
        }
        self::assertInstanceOf(\InvalidArgumentException::class, $e);
        self::assertEquals(1733597950, $e->getCode());

        try {
            $this->subscriptionEngine->catchUpActive(batchSize: -1);
        } catch (\Throwable $e) {
        }

        self::assertInstanceOf(\InvalidArgumentException::class, $e);
        self::assertEquals(1733597950, $e->getCode());
    }
}
