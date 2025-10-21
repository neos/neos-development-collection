<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription;

use Doctrine\DBAL\Connection;
use Neos\Behat\FlowEntitiesTrait;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceRepository;

final class CatchUpHookWithPersistenceTest extends AbstractSubscriptionEngineTestCase
{
    use FlowEntitiesTrait;

    /**
     * @before
     */
    public function setupFlowEntities()
    {
        $this->truncateAndSetupFlowEntities();
    }

    /** @test */
    public function commitOnConnection_onAfterEvent()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::once())->method('apply');
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();

        // commit two events. but only the first will never be seen
        $this->commitExampleContentStreamEvent();
        $this->commitExampleContentStreamEvent();

        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::ACTIVE);
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class));
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterEvent')->willReturnCallback(function () {
            $this->getObject(Connection::class)->commit();
        });
        $this->catchupHookForSecondFakeProjection->expects(self::never())->method('onAfterCatchUp');

        self::assertEmpty(
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        $actualException = null;
        try {
            $this->subscriptionEngine->catchUpActive(batchSize: 1);
        } catch (\Throwable $e) {
            $actualException = $e;
        }
        // To solve this we would need to use an own connection for all CORE cr parts.
        self::assertInstanceOf(\Doctrine\DBAL\ConnectionException::class, $actualException);
        self::assertEquals('There is no active transaction.', $actualException->getMessage());

        self::assertFalse($this->getObject(Connection::class)->isTransactionActive());

        // partially applied event because the error is thrown at the end and the projection is not rolled back
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));
        self::assertEquals(
            [1],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues()
        );
    }

    /** @test */
    public function persistAll_onAfterEvent_willUseTheTransaction()
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::once())->method('apply');
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();

        // commit one event
        $this->commitExampleContentStreamEvent();

        $persistentResource = new PersistentResource();
        $persistentResource->disableLifecycleEvents();
        $persistentResource->setFilename($expectedName = 'test_cr_catchup.empty');
        $persistentResource->setFileSize(0);
        $persistentResource->setCollectionName('default');
        $persistentResource->setMediaType('text/plain');
        $persistentResource->setSha1($sha1 = '67f22467d829a254d53fa5cf019787c23c57bbef');

        self::assertTrue($this->getObject(PersistenceManagerInterface::class)->isNewObject($persistentResource));

        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeCatchUp')->with(SubscriptionStatus::ACTIVE);
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onBeforeEvent')->with(self::isInstanceOf(ContentStreamWasCreated::class));
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterEvent')->willReturnCallback(function () use ($persistentResource) {
            $this->getObject(ResourceRepository::class)->add($persistentResource);
            $this->getObject(PersistenceManagerInterface::class)->persistAll();
        });
        $this->catchupHookForSecondFakeProjection->expects(self::once())->method('onAfterCatchUp');

        self::assertEmpty(
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        $result = $this->subscriptionEngine->catchUpActive();
        self::assertNull($result->errors);

        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));
        self::assertEquals(
            [SequenceNumber::fromInteger(1)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );

        // check that the object was persisted and re-fetch it from the database
        self::assertFalse($this->getObject(PersistenceManagerInterface::class)->isNewObject($persistentResource));
        $this->getObject(PersistenceManagerInterface::class)->clearState();

        $actuallyPersisted = $this->getObject(ResourceRepository::class)->findOneBySha1($sha1);

        self::assertEquals($sha1, $actuallyPersisted->getSha1());
        self::assertEquals($expectedName, $actuallyPersisted->getFilename());
    }
}
