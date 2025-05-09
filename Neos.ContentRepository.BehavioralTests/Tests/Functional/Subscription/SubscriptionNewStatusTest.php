<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription;

use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Subscription\Engine\ProcessedResult;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngineCriteria;
use Neos\ContentRepository\Core\Subscription\ProjectionSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepository\TestSuite\Fakes\FakeProjectionFactory;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\Flow\Configuration\ConfigurationManager;

final class SubscriptionNewStatusTest extends AbstractSubscriptionEngineTestCase
{
    /** @after */
    public function resetContentRepositoryRegistry(): void
    {
        $originalSettings = $this->getObject(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.ContentRepositoryRegistry');
        $this->getObject(ContentRepositoryRegistry::class)->injectSettings($originalSettings);
    }

    /** @test */
    public function newProjectionIsFoundWhenConfigurationIsAdded()
    {
        $this->fakeProjection->expects(self::exactly(2))->method('setUp');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());

        $this->eventStore->setup();
        $this->subscriptionEngine->setup();

        $result = $this->subscriptionEngine->boot();
        self::assertEquals(ProcessedResult::success(0), $result);

        self::assertNull($this->subscriptionStatus('Vendor.Package:NewFakeProjection'));
        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::none());

        $newFakeProjection = $this->getMockBuilder(ProjectionInterface::class)->disableAutoReturnValueGeneration()->getMock();
        $newFakeProjection->method('getState')->willReturn(new class implements ProjectionStateInterface {});
        $newFakeProjection->expects(self::exactly(5))->method('status')->willReturnOnConsecutiveCalls(
            ProjectionStatus::setupRequired('Set me up'),
            ProjectionStatus::setupRequired('Set me up'),
            ProjectionStatus::setupRequired('Set me up'),
            ProjectionStatus::ok(),
            ProjectionStatus::ok(),
        );

        FakeProjectionFactory::setProjection(
            'newFake',
            $newFakeProjection
        );

        $mockSettings = $this->getObject(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.ContentRepositoryRegistry');
        $mockSettings['contentRepositories'][$this->contentRepository->id->value]['projections']['Vendor.Package:NewFakeProjection'] = [
            'factoryObjectName' => FakeProjectionFactory::class,
            'options' => [
                'instanceId' => 'newFake'
            ]
        ];
        $this->getObject(ContentRepositoryRegistry::class)->injectSettings($mockSettings);
        $this->getObject(ContentRepositoryRegistry::class)->resetFactoryInstance($this->contentRepository->id);
        $this->setupContentRepositoryDependencies($this->contentRepository->id);

        $expectedNewState = ProjectionSubscriptionStatus::create(
            subscriptionId: SubscriptionId::fromString('Vendor.Package:NewFakeProjection'),
            subscriptionStatus: SubscriptionStatus::NEW,
            subscriptionPosition: SequenceNumber::none(),
            subscriptionError: null,
            setupStatus: ProjectionStatus::setupRequired('Set me up')
        );

        // status predicts the NEW state already (without creating it in the db)
        self::assertEquals(
            $expectedNewState,
            $this->subscriptionStatus('Vendor.Package:NewFakeProjection')
        );

        // do something that finds new subscriptions, trigger a setup on a specific projection:
        $result = $this->subscriptionEngine->setup(SubscriptionEngineCriteria::create([SubscriptionId::fromString('contentGraph')]));
        self::assertNull($result->errors);

        self::assertEquals(
            $expectedNewState,
            $this->subscriptionStatus('Vendor.Package:NewFakeProjection')
        );

        // reset is a noop and skips this NEW projection!
        $result = $this->subscriptionEngine->reset(SubscriptionEngineCriteria::create([SubscriptionId::fromString('Vendor.Package:NewFakeProjection')]));
        self::assertNull($result->errors);
        // still new and NOT booting!
        self::assertEquals(
            $expectedNewState,
            $this->subscriptionStatus('Vendor.Package:NewFakeProjection')
        );

        // setup this projection
        $newFakeProjection->expects(self::once())->method('setUp');
        $result = $this->subscriptionEngine->setup();
        self::assertNull($result->errors);

        $this->expectOkayStatus('Vendor.Package:NewFakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::none());

        $result = $this->subscriptionEngine->boot();
        self::assertNull($result->errors);
        $this->expectOkayStatus('Vendor.Package:NewFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::none());
    }
}
