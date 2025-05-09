<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Neos\ContentRepository\BehavioralTests\TestSuite\DebugEventProjection;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Subscription\ProjectionSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionError;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\Event\SequenceNumber;

/**
 * Note that this test only documents the current state, there were ideas to guarantee exactly once delivery for external projections
 * but this would mean additional complexity and is technically never truly possible with two connections.
 * The most likely path this might work fully is by the introduction of a decentralized subscription store concept,
 * see https://github.com/neos/neos-development-collection/pull/5377, but this is out of scope for now until we find good reasons.
 */
final class ExternalProjectionErrorTest extends AbstractSubscriptionEngineTestCase
{
    static Connection $secondConnection;

    /** @before */
    public function injectExternalFakeProjection(): void
    {
        $entityManager = $this->getObject(EntityManagerInterface::class);

        if (!isset(self::$secondConnection)) {
            self::$secondConnection = DriverManager::getConnection(
                $entityManager->getConnection()->getParams(),
                $entityManager->getConfiguration(),
                $entityManager->getEventManager()
            );
        }

        $this->secondFakeProjection = new DebugEventProjection(
            sprintf('cr_%s_debug_projection', self::$contentRepositoryId->value),
            self::$secondConnection
        );
    }

    public static function tearDownAfterClass(): void
    {
        self::$secondConnection->close();
    }

    /** @test */
    public function externalProjectionIsNotRolledBackAfterError()
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

        // not empty as the projection is commited directly
        self::assertEquals(
            [SequenceNumber::fromInteger(1)],
            $this->secondFakeProjection->getState()->findAppliedSequenceNumbers()
        );
    }
}
