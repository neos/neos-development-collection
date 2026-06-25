<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Tests\Functional\Upgrade\ResetupAndReplayContentGraph;

use Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription\AbstractSubscriptionEngineTestCase;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainerFactory;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngineCriteria;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Upgrade\Command\CRUpgradeContextFactory;
use Neos\ContentRepositoryRegistry\Upgrade\ResetupAndReplayContentGraph\ResetupAndReplayContentGraphUpgrade;
use Neos\EventStore\Model\Event\SequenceNumber;
use PHPUnit\Framework\Assert;

class ResetupAndReplayContentGraphUpgradeTest extends AbstractSubscriptionEngineTestCase
{
    private ResetupAndReplayContentGraphUpgrade $resetGraphAndSetupUpgrade;

    private array $outputLines = [];

    /** @test */
    public function resetupAndReplayOnEmptyEventStore()
    {
        $this->fakeProjection->expects(self::exactly(2))->method('setUp');
        $this->fakeProjection->expects(self::never())->method('apply');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());

        $this->eventStore->setup();

        // initial setup
        $result = $this->subscriptionEngine->setup();
        self::assertNull($result->errors);
        $result = $this->subscriptionEngine->boot();
        self::assertNull($result->errors);

        // up to date graph
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(0));

        // attempt to reset
        $this->resetGraphAndSetupUpgrade->execute(force: false);
        $this->assertTheOutputEquals(<<<MESSAGE
            DEBUG: Content graph projection schema status "OK". Subscription was at sequence 0 of available 0 with status "ACTIVE"
        
        Content graph projection is fully set-up. See `flow cr:status`. Not continuing emptying the projection. If you know what you are doing try again by using a bit more force.
        MESSAGE);
        // unchanged
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(0));

        // force reset which - simulate that a reset is required
        $this->resetGraphAndSetupUpgrade->execute(force: true);
        $this->assertTheOutputEquals(<<<MESSAGE
        Dropped all existing graph projection tables.
        Running schema setup ...
        Content repository "t_subscription" was set up
        Replaying the content graph projection (without invoking its catchup hooks) ...
        MESSAGE);
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(0));
    }

    /** @test */
    public function resetupAndReplayBecauseNewMigrationWouldFail()
    {
        $this->fakeProjection->expects(self::exactly(2))->method('setUp');
        $this->fakeProjection->expects(self::exactly(2))->method('apply');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());

        $this->eventStore->setup();

        // initial setup
        $result = $this->subscriptionEngine->setup();
        self::assertNull($result->errors);
        $result = $this->subscriptionEngine->boot();
        self::assertNull($result->errors);
        $this->contentRepository->handle(CreateRootWorkspace::create(WorkspaceName::fromString('root'), ContentStreamId::fromString('root-cs')));

        // up to date graph
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
        self::assertNotNull($this->contentRepository->findWorkspaceByName(WorkspaceName::fromString('root')));

        // attempt to reset
        $this->resetGraphAndSetupUpgrade->execute(force: false);
        $this->assertTheOutputEquals(<<<MESSAGE
            DEBUG: Content graph projection schema status "OK". Subscription was at sequence 2 of available 2 with status "ACTIVE"
        
        Content graph projection is fully set-up. See `flow cr:status`. Not continuing emptying the projection. If you know what you are doing try again by using a bit more force.
        MESSAGE);
        // unchanged
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
        // tables are not emptied
        self::assertNotNull($this->contentRepository->findWorkspaceByName(WorkspaceName::fromString('root')));

        // force reset which - simulate that a reset is required
        $this->resetGraphAndSetupUpgrade->execute(force: true);
        $this->assertTheOutputEquals(<<<MESSAGE
        Dropped all existing graph projection tables.
        Running schema setup ...
        Content repository "t_subscription" was set up
        Replaying the content graph projection (without invoking its catchup hooks) ...
        MESSAGE);
        // graph is up to date again
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
        self::assertNotNull($this->contentRepository->findWorkspaceByName(WorkspaceName::fromString('root')));

        // other subscriptions are not reset!
        self::assertSame([1, 2], $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues());
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
    }

    /** @test */
    public function constraintBeforeReplayIfGraphIsNotUpToDate()
    {
        $this->fakeProjection->expects(self::exactly(2))->method('setUp');
        $this->fakeProjection->expects(self::exactly(2))->method('apply');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());

        $this->eventStore->setup();

        // initial setup
        $result = $this->subscriptionEngine->setup();
        self::assertNull($result->errors);
        $result = $this->subscriptionEngine->boot();
        self::assertNull($result->errors);
        $this->contentRepository->handle(CreateRootWorkspace::create(WorkspaceName::fromString('root'), ContentStreamId::fromString('root-cs')));
        $this->commitExampleContentStreamEvent();

        // almost up to date graph
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
        self::assertNotNull($this->contentRepository->findWorkspaceByName(WorkspaceName::fromString('root')));

        // attempt to reset
        $this->resetGraphAndSetupUpgrade->execute(force: false);
        $this->assertTheOutputEquals(<<<MESSAGE
            DEBUG: Content graph projection schema status "OK". Subscription was at sequence 2 of available 3 with status "ACTIVE"
        
        Content graph projection is with position 2 behind event-store position 3. See `flow cr:status`. Not continuing not replaying the projection as we will not invoke catchup hooks for the graph events after 2. If you know what you are doing try again by using a bit more force.
        MESSAGE);
        // unchanged
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
        // tables are not emptied
        self::assertNotNull($this->contentRepository->findWorkspaceByName(WorkspaceName::fromString('root')));

        // force reset which - simulate that a reset is required
        $this->resetGraphAndSetupUpgrade->execute(force: true);
        $this->assertTheOutputEquals(<<<MESSAGE
        Dropped all existing graph projection tables.
        Running schema setup ...
        Content repository "t_subscription" was set up
        Replaying the content graph projection (without invoking its catchup hooks) ...
        MESSAGE);
        // graph is now fully to date again (3)
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(3));
        self::assertNotNull($this->contentRepository->findWorkspaceByName(WorkspaceName::fromString('root')));

        // other subscriptions are not replayed!
        self::assertSame([1, 2], $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues());
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
    }

    protected function outputFn(string $message): void
    {
        $this->outputLines[] = $message;
    }

    public function assertTheOutputEquals(string $string): void
    {
        Assert::assertSame($string, join(PHP_EOL, $this->outputLines));
        $this->outputLines = [];
    }

    public function setUp(): void
    {
        parent::setUp();

        $contentRepositoryRegistry = $this->getObject(ContentRepositoryRegistry::class);

        $context = $contentRepositoryRegistry->buildService(
            $this->contentRepository->id,
            $this->getObject(CRUpgradeContextFactory::class)
        );

        $upgrade = new ResetupAndReplayContentGraphUpgrade(
            $context,
            $this->outputFn(...),
            fn () => null,
            $contentRepositoryRegistry->buildService(
                $context->contentRepositoryId,
                new ContentRepositoryMaintainerFactory()
            )
        );

        $this->resetGraphAndSetupUpgrade = $upgrade;
    }

    /**
     * @after
     */
    public function noUnmatchedOutputLines(): void
    {
        $this->assertTheOutputEquals('');
    }
}
