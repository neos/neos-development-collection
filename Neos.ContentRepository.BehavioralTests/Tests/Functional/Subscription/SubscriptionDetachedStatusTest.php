<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription;

use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Subscription\DetachedSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\Engine\ProcessedResult;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngineCriteria;
use Neos\ContentRepository\Core\Subscription\ProjectionSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\Flow\Configuration\ConfigurationManager;

final class SubscriptionDetachedStatusTest extends AbstractSubscriptionEngineTestCase
{
    /** @after */
    public function resetContentRepositoryRegistry(): void
    {
        $originalSettings = $this->getObject(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.ContentRepositoryRegistry');
        $this->getObject(ContentRepositoryRegistry::class)->injectSettings($originalSettings);
    }

    /** @test */
    public function projectionIsDetachedOnCatchupActive()
    {
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());

        $this->eventStore->setup();
        $this->subscriptionEngine->setup();

        $result = $this->subscriptionEngine->boot();
        self::assertEquals(ProcessedResult::success(0), $result);

        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::none());

        // commit an event
        $this->commitExampleContentStreamEvent();

        $this->uninstallProjection('Vendor.Package:FakeProjection');

        self::assertEquals(
            DetachedSubscriptionStatus::create(
                subscriptionId: SubscriptionId::fromString('Vendor.Package:FakeProjection'),
                // the state is still active as we do not mutate it during the setup call!
                subscriptionStatus: SubscriptionStatus::ACTIVE,
                subscriptionPosition: SequenceNumber::none()
            ),
            $this->subscriptionStatus('Vendor.Package:FakeProjection')
        );

        $this->fakeProjection->expects(self::never())->method('apply');
        // catchup to mark detached subscribers
        $result = $this->subscriptionEngine->catchUpActive();
        self::assertEquals(ProcessedResult::success(1), $result);

        self::assertEquals(
            $expectedDetachedState = DetachedSubscriptionStatus::create(
                subscriptionId: SubscriptionId::fromString('Vendor.Package:FakeProjection'),
                subscriptionStatus: SubscriptionStatus::DETACHED,
                subscriptionPosition: SequenceNumber::none()
            ),
            $this->subscriptionStatus('Vendor.Package:FakeProjection')
        );

        // other projections are not interrupted
        self::assertEquals(
            [SequenceNumber::fromInteger(1)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        // succeeding catchup's do not handle the detached one:
        $this->commitExampleContentStreamEvent();
        $result = $this->subscriptionEngine->catchUpActive();
        self::assertEquals(ProcessedResult::success(1), $result);

        self::assertEquals(
            [SequenceNumber::fromInteger(1), SequenceNumber::fromInteger(2)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        // still detached
        self::assertEquals(
            $expectedDetachedState,
            $this->subscriptionStatus('Vendor.Package:FakeProjection')
        );

        // setup is a noop:
        $result = $this->subscriptionEngine->setup(SubscriptionEngineCriteria::create([SubscriptionId::fromString('Vendor.Package:FakeProjection')]));
        self::assertNull($result->errors);

        // still detached
        self::assertEquals(
            $expectedDetachedState,
            $this->subscriptionStatus('Vendor.Package:FakeProjection')
        );

        // reset is a noop:
        $result = $this->subscriptionEngine->reset(SubscriptionEngineCriteria::create([SubscriptionId::fromString('Vendor.Package:FakeProjection')]));
        self::assertNull($result->errors);

        // still detached
        self::assertEquals(
            $expectedDetachedState,
            $this->subscriptionStatus('Vendor.Package:FakeProjection')
        );
    }

    /** @test */
    public function projectionIsDetachedOnSetupAndReattachedIfPossible()
    {
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::once())->method('apply');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());

        $this->eventStore->setup();
        $this->subscriptionEngine->setup();

        $this->commitExampleContentStreamEvent();

        $result = $this->subscriptionEngine->boot();
        self::assertEquals(ProcessedResult::success(1), $result);

        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));

        // "uninstall" the projection
        $this->uninstallProjection('Vendor.Package:FakeProjection');

        $this->fakeProjection->expects(self::never())->method('apply');
        // setup to find detached subscribers
        $result = $this->subscriptionEngine->setup();
        self::assertNull($result->errors);

        $expectedDetachedState = DetachedSubscriptionStatus::create(
            subscriptionId: SubscriptionId::fromString('Vendor.Package:FakeProjection'),
            subscriptionStatus: SubscriptionStatus::DETACHED,
            subscriptionPosition: SequenceNumber::fromInteger(1)
        );

        self::assertEquals(
            $expectedDetachedState,
            $this->subscriptionStatus('Vendor.Package:FakeProjection')
        );

        // "reinstall" the projection
        $this->reinstallProjections();

        self::assertEquals(
            ProjectionSubscriptionStatus::create(
                subscriptionId: SubscriptionId::fromString('Vendor.Package:FakeProjection'),
                subscriptionStatus: SubscriptionStatus::DETACHED,
                subscriptionPosition: SequenceNumber::fromInteger(1),
                subscriptionError: null,
                setupStatus: ProjectionStatus::ok()
            ),
            $this->subscriptionStatus('Vendor.Package:FakeProjection')
        );

        // reactivate does re-attach as the projection if its found again
        $result = $this->subscriptionEngine->reactivate(SubscriptionEngineCriteria::create([SubscriptionId::fromString('Vendor.Package:FakeProjection')]));
        self::assertNull($result->errors);

        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));
    }

    /** @test */
    public function projectionIsDetachedOnSetupAndReattachedViaResetIfPossible()
    {
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::once())->method('apply');
        $this->fakeProjection->expects(self::once())->method('resetState');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());

        $this->eventStore->setup();
        $this->subscriptionEngine->setup();

        $this->commitExampleContentStreamEvent();

        $result = $this->subscriptionEngine->boot();
        self::assertEquals(ProcessedResult::success(1), $result);

        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));

        // "uninstall" the projection
        $this->uninstallProjection('Vendor.Package:FakeProjection');

        $this->fakeProjection->expects(self::never())->method('apply');
        // setup to find detached subscribers
        $result = $this->subscriptionEngine->setup();
        self::assertNull($result->errors);

        $expectedDetachedState = DetachedSubscriptionStatus::create(
            subscriptionId: SubscriptionId::fromString('Vendor.Package:FakeProjection'),
            subscriptionStatus: SubscriptionStatus::DETACHED,
            subscriptionPosition: SequenceNumber::fromInteger(1)
        );

        self::assertEquals(
            $expectedDetachedState,
            $this->subscriptionStatus('Vendor.Package:FakeProjection')
        );

        // "reinstall" the projection
        $this->reinstallProjections();

        // reset does re-attach the projection if its found again
        $result = $this->subscriptionEngine->reset(SubscriptionEngineCriteria::create([SubscriptionId::fromString('Vendor.Package:FakeProjection')]));
        self::assertNull($result->errors);

        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::fromInteger(0));
    }

    private function uninstallProjection(string $projectionName): void
    {
        $originalSettings = $this->getObject(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.ContentRepositoryRegistry');
        $mockSettings = $originalSettings;
        unset($mockSettings['contentRepositories'][$this->contentRepository->id->value]['projections'][$projectionName]);
        $this->getObject(ContentRepositoryRegistry::class)->injectSettings($mockSettings);
        $this->getObject(ContentRepositoryRegistry::class)->resetFactoryInstance($this->contentRepository->id);
        $this->setupContentRepositoryDependencies($this->contentRepository->id);
    }

    private function reinstallProjections(): void
    {
        $this->resetContentRepositoryRegistry();
        $this->getObject(ContentRepositoryRegistry::class)->resetFactoryInstance($this->contentRepository->id);
        $this->setupContentRepositoryDependencies($this->contentRepository->id);
    }
}
