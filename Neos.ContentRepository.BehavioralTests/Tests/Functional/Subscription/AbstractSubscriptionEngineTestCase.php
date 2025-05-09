<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Neos\ContentRepository\BehavioralTests\TestSuite\DebugEventProjection;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Projection\CatchUpHook\CatchUpHookInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\Subscription\DetachedSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngine;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngineCriteria;
use Neos\ContentRepository\Core\Subscription\ProjectionSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepository\TestSuite\Fakes\FakeCatchUpHookFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeCatchUpHookFactory2;
use Neos\ContentRepository\TestSuite\Fakes\FakeContentDimensionSourceFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeNodeTypeManagerFactory;
use Neos\ContentRepository\TestSuite\Fakes\FakeProjectionFactory;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\Flow\Core\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal, only for tests of the Neos.* namespace
 */
abstract class AbstractSubscriptionEngineTestCase extends TestCase // we don't use Flows functional test case as it would reset the database afterwards
{
    protected static ContentRepositoryId $contentRepositoryId;

    protected ContentRepository $contentRepository;

    protected SubscriptionEngine $subscriptionEngine;

    protected EventStoreInterface $eventStore;

    protected ProjectionInterface&MockObject $fakeProjection;

    protected DebugEventProjection $secondFakeProjection;

    protected CatchUpHookInterface&MockObject $catchupHookForFakeProjection;

    protected CatchUpHookInterface&MockObject $catchupHookForSecondFakeProjection;

    protected CatchUpHookInterface&MockObject $additionalCatchupHookForSecondFakeProjection;

    public static function setUpBeforeClass(): void
    {
        static::$contentRepositoryId = ContentRepositoryId::fromString('t_subscription');
    }

    public function setUp(): void
    {
        if ($this->getObject(Connection::class)->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            $this->markTestSkipped('TODO: The content graph is not available in postgres currently: https://github.com/neos/neos-development-collection/issues/3855');
        }

        $this->resetDatabase(
            $this->getObject(Connection::class),
            self::$contentRepositoryId,
            keepSchema: true
        );

        $this->fakeProjection = $this->getMockBuilder(ProjectionInterface::class)->disableAutoReturnValueGeneration()->getMock();
        $this->fakeProjection->method('getState')->willReturn(new class implements ProjectionStateInterface {});

        FakeProjectionFactory::setProjection(
            'default',
            $this->fakeProjection
        );

        if (!isset($this->secondFakeProjection)) {
            $this->secondFakeProjection = new DebugEventProjection(
                sprintf('cr_%s_debug_projection', self::$contentRepositoryId->value),
                $this->getObject(Connection::class)
            );
        }

        FakeProjectionFactory::setProjection(
            'second',
            $this->secondFakeProjection
        );

        $this->catchupHookForFakeProjection = $this->getMockBuilder(CatchUpHookInterface::class)->getMock();
        FakeCatchUpHookFactory::setCatchupHook(
            $this->fakeProjection->getState(),
            $this->catchupHookForFakeProjection
        );

        $this->catchupHookForSecondFakeProjection = $this->getMockBuilder(CatchUpHookInterface::class)->getMock();
        FakeCatchUpHookFactory::setCatchupHook(
            $this->secondFakeProjection->getState(),
            $this->catchupHookForSecondFakeProjection
        );

        $this->additionalCatchupHookForSecondFakeProjection = $this->getMockBuilder(CatchUpHookInterface::class)->getMock();
        FakeCatchUpHookFactory2::setCatchupHook(
            $this->secondFakeProjection->getState(),
            $this->additionalCatchupHookForSecondFakeProjection
        );

        FakeNodeTypeManagerFactory::setConfiguration([]);
        FakeContentDimensionSourceFactory::setWithoutDimensions();

        $this->getObject(ContentRepositoryRegistry::class)->resetFactoryInstance(self::$contentRepositoryId);

        $this->setupContentRepositoryDependencies(self::$contentRepositoryId);
    }

    final protected function setupContentRepositoryDependencies(ContentRepositoryId $contentRepositoryId)
    {
        $this->contentRepository = $this->getObject(ContentRepositoryRegistry::class)->get(
            $contentRepositoryId
        );

        $subscriptionEngineAndEventStoreAccessor = new class implements ContentRepositoryServiceFactoryInterface {
            public EventStoreInterface|null $eventStore;
            public SubscriptionEngine|null $subscriptionEngine;
            public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
            {
                $this->eventStore = $serviceFactoryDependencies->eventStore;
                $this->subscriptionEngine = $serviceFactoryDependencies->subscriptionEngine;
                return new class implements ContentRepositoryServiceInterface
                {
                };
            }
        };
        $this->getObject(ContentRepositoryRegistry::class)->buildService($contentRepositoryId, $subscriptionEngineAndEventStoreAccessor);
        $this->eventStore = $subscriptionEngineAndEventStoreAccessor->eventStore;
        $this->subscriptionEngine = $subscriptionEngineAndEventStoreAccessor->subscriptionEngine;
    }

    final protected function resetDatabase(Connection $connection, ContentRepositoryId $contentRepositoryId, bool $keepSchema): void
    {
        $preDeleteStatement = match (true) {
            $connection->getDatabasePlatform() instanceof AbstractMySQLPlatform => 'SET FOREIGN_KEY_CHECKS = 0;',
            default => '',
        };

        if ($preDeleteStatement !== '') {
            $connection->prepare($preDeleteStatement)->executeStatement();
        }

        $truncateOrDropStatement = match (true) {
            $connection->getDatabasePlatform() instanceof PostgreSQLPlatform => '%s TABLE `%s` CASCADE',
            default => '%s TABLE `%s`',
        };

        foreach ($connection->createSchemaManager()->listTableNames() as $tableName) {
            if (!str_starts_with($tableName, sprintf('cr_%s_', $contentRepositoryId->value))) {
                // speedup deletion, only delete current cr
                continue;
            }
            // truncate is faster
            $sql = sprintf($truncateOrDropStatement, $keepSchema ? 'TRUNCATE' : 'DROP', $tableName);
            $connection->prepare($sql)->executeStatement();
        }

        $postDeleteStatement = match (true) {
            $connection->getDatabasePlatform() instanceof AbstractMySQLPlatform => 'SET FOREIGN_KEY_CHECKS = 1;',
            default => '',
        };

        if ($postDeleteStatement !== '') {
            $connection->prepare($postDeleteStatement)->executeStatement();
        }
    }

    final protected function subscriptionStatus(string $subscriptionId): ProjectionSubscriptionStatus|DetachedSubscriptionStatus|null
    {
        return $this->subscriptionEngine->subscriptionStatus(SubscriptionEngineCriteria::create(ids: [SubscriptionId::fromString($subscriptionId)]))->first();
    }

    final protected function commitExampleContentStreamEvent(): void
    {
        $this->eventStore->commit(
            ContentStreamEventStreamName::fromContentStreamId($cs = ContentStreamId::create())->getEventStreamName(),
            new Event(
                Event\EventId::create(),
                Event\EventType::fromString('ContentStreamWasCreated'),
                Event\EventData::fromString(json_encode(['contentStreamId' => $cs->value]))
            ),
            ExpectedVersion::NO_STREAM()
        );
    }

    final protected function expectOkayStatus(string $subscriptionId, SubscriptionStatus $status, SequenceNumber $sequenceNumber): void
    {
        $actual = $this->subscriptionStatus($subscriptionId);
        self::assertEquals(
            ProjectionSubscriptionStatus::create(
                subscriptionId: SubscriptionId::fromString($subscriptionId),
                subscriptionStatus: $status,
                subscriptionPosition: $sequenceNumber,
                subscriptionError: null,
                setupStatus: ProjectionStatus::ok(),
            ),
            $actual
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    final protected function getObject(string $className): object
    {
        return Bootstrap::$staticObjectManager->get($className);
    }
}
