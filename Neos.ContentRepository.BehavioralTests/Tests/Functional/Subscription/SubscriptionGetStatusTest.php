<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainerFactory;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngineCriteria;
use Neos\ContentRepository\Core\Subscription\ProjectionSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatusCollection;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventStore\StatusType;

final class SubscriptionGetStatusTest extends AbstractSubscriptionEngineTestCase
{
    /** @test */
    public function statusOnEmptyDatabase()
    {
        // fully drop the tables so that status has to recover if the subscriptions table is not there
        $this->resetDatabase(
            $this->getObject(Connection::class),
            $this->contentRepository->id,
            keepSchema: false
        );

        $crMaintainer = $this->getObject(ContentRepositoryRegistry::class)->buildService($this->contentRepository->id, new ContentRepositoryMaintainerFactory());

        $status = $crMaintainer->status();

        self::assertEquals(StatusType::SETUP_REQUIRED, $status->eventStoreStatus->type);
        self::assertNull($status->eventStorePosition);
        self::assertTrue($status->subscriptionStatus->isEmpty());

        self::assertNull(
            $this->subscriptionStatus('contentGraph')
        );
        self::assertNull(
            $this->subscriptionStatus('Vendor.Package:FakeProjection')
        );
        self::assertNull(
            $this->subscriptionStatus('Vendor.Package:SecondFakeProjection')
        );

        //
        // setup and fetch status
        //

        // only setup content graph so that the other projections are NEW, but still found
        $this->subscriptionEngine->setup(SubscriptionEngineCriteria::create([SubscriptionId::fromString('contentGraph')]));
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::BOOTING, SequenceNumber::none());

        $this->fakeProjection->expects(self::once())->method('status')->willReturn(ProjectionStatus::setupRequired('fake needs setup.'));

        $actualStatuses = $this->subscriptionEngine->subscriptionStatus();

        $expected = SubscriptionStatusCollection::fromArray([
            ProjectionSubscriptionStatus::create(
                subscriptionId: SubscriptionId::fromString('contentGraph'),
                subscriptionStatus: SubscriptionStatus::BOOTING,
                subscriptionPosition: SequenceNumber::none(),
                subscriptionError: null,
                setupStatus: ProjectionStatus::ok(),
            ),
            ProjectionSubscriptionStatus::create(
                subscriptionId: SubscriptionId::fromString('Vendor.Package:FakeProjection'),
                subscriptionStatus: SubscriptionStatus::NEW,
                subscriptionPosition: SequenceNumber::none(),
                subscriptionError: null,
                setupStatus: ProjectionStatus::setupRequired('fake needs setup.'),
            ),
            ProjectionSubscriptionStatus::create(
                subscriptionId: SubscriptionId::fromString('Vendor.Package:SecondFakeProjection'),
                subscriptionStatus: SubscriptionStatus::NEW,
                subscriptionPosition: SequenceNumber::none(),
                subscriptionError: null,
                setupStatus: ProjectionStatus::setupRequired('Requires 1 SQL statements'),
            ),
        ]);

        self::assertEquals($expected, $actualStatuses);
    }
}
