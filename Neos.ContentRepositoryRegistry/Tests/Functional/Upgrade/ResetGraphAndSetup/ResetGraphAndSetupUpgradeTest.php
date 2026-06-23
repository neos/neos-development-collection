<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Tests\Functional\Upgrade\ResetGraphAndSetup;

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
use Neos\ContentRepositoryRegistry\Upgrade\ResetGraphAndSetup\ResetGraphAndSetupUpgrade;
use Neos\EventStore\Model\Event\SequenceNumber;
use PHPUnit\Framework\Assert;

class ResetGraphAndSetupUpgradeTest extends AbstractSubscriptionEngineTestCase
{
    private ResetGraphAndSetupUpgrade $resetGraphAndSetupUpgrade;

    private array $outputLines = [];


    /** @test */
    public function resetAndSetupBecauseNewMigrationWouldFail()
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
            DEBUG: Content graph projection schema status "OK". Subscription was at sequence number 2 with status "ACTIVE"
        
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
        The graph projection now needs to be replayed: `./flow subscription:replay contentGraph`
        MESSAGE);
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::BOOTING, SequenceNumber::none());
        // no workspace is found - tables are empty
        self::assertNull($this->contentRepository->findWorkspaceByName(WorkspaceName::fromString('root')));

        // other subscriptions are not reset!
        self::assertSame([1, 2], $this->secondFakeProjection->getState()->findAppliedSequenceNumberValues());
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));

        // replay again
        $result = $this->subscriptionEngine->boot(SubscriptionEngineCriteria::create(['contentGraph']));
        self::assertNull($result->errors);
        // up to date graph
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
        self::assertNotNull($this->contentRepository->findWorkspaceByName(WorkspaceName::fromString('root')));
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

        $upgrade = new ResetGraphAndSetupUpgrade(
            $context,
            $this->outputFn(...),
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
