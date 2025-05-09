<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Functional;

use Neos\ContentRepository\BehavioralTests\Tests\Functional\Subscription\AbstractSubscriptionEngineTestCase;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepositoryRegistry\Command\CrCommandController;
use Neos\ContentRepositoryRegistry\Command\SubscriptionCommandController;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Flow\Cli\Response;
use Neos\Utility\ObjectAccess;
use Symfony\Component\Console\Output\BufferedOutput;

final class ContentRepositoryMaintenanceCommandControllerTest extends AbstractSubscriptionEngineTestCase
{
    private CrCommandController $crController;

    private SubscriptionCommandController $subscriptionController;

    private Response $response;

    private BufferedOutput $bufferedOutput;

    protected static bool $strictFakeProjection = false;

    /** @before */
    public function injectController(): void
    {
        $this->crController = $this->getObject(CrCommandController::class);
        $this->subscriptionController = $this->getObject(SubscriptionCommandController::class);

        $this->response = new Response();
        $this->bufferedOutput = new BufferedOutput();

        ObjectAccess::setProperty($this->crController, 'response', $this->response, true);
        ObjectAccess::getProperty($this->crController, 'output', true)->setOutput($this->bufferedOutput);

        ObjectAccess::setProperty($this->subscriptionController, 'response', $this->response, true);
        ObjectAccess::getProperty($this->subscriptionController, 'output', true)->setOutput($this->bufferedOutput);
    }

    /** @test */
    public function setupOnEmptyEventStore(): void
    {
        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());

        $this->crController->setupCommand(contentRepository: $this->contentRepository->id->value, quiet: true);
        self::assertEmpty($this->bufferedOutput->fetch());

        // projections are marked active because the event store is empty
        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::none());
        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::none());
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::none());

        $this->crController->statusCommand(contentRepository: $this->contentRepository->id->value, quiet: true);
        self::assertEmpty($this->bufferedOutput->fetch());
    }

    /** @test */
    public function setupOnModifiedEventStore(): void
    {
        $this->eventStore->setup();
        $this->commitExampleContentStreamEvent();

        $this->fakeProjection->expects(self::once())->method('setUp');
        $this->fakeProjection->expects(self::once())->method('apply');
        $this->fakeProjection->expects(self::once())->method('resetState');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());

        $this->crController->setupCommand(contentRepository: $this->contentRepository->id->value, quiet: true);
        self::assertEmpty($this->bufferedOutput->fetch());

        $this->expectOkayStatus('contentGraph', SubscriptionStatus::BOOTING, SequenceNumber::none());
        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::none());
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::none());

        $this->crController->statusCommand(contentRepository: $this->contentRepository->id->value, quiet: true);
        self::assertEmpty($this->bufferedOutput->fetch());

        $this->subscriptionController->replayCommand(subscription: 'contentGraph', contentRepository: $this->contentRepository->id->value, force: true, quiet: true);
        self::assertEmpty($this->bufferedOutput->fetch());

        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));
        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::none());
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::BOOTING, SequenceNumber::none());

        $this->subscriptionController->replayAllCommand(contentRepository: $this->contentRepository->id->value, force: true, quiet: true);

        $this->expectOkayStatus('contentGraph', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));
        $this->expectOkayStatus('Vendor.Package:FakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));
        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(1));
    }

    /** @test */
    public function projectionInError(): void
    {
        $this->eventStore->setup();
        $this->fakeProjection->expects(self::any())->method('setUp');
        $this->fakeProjection->expects(self::any())->method('apply');
        $this->fakeProjection->expects(self::any())->method('status')->willReturn(ProjectionStatus::ok());

        $this->crController->setupCommand(contentRepository: $this->contentRepository->id->value, quiet: true);
        self::assertEmpty($this->bufferedOutput->fetch());

        $this->secondFakeProjection->injectSaboteur(fn () => throw new \RuntimeException('This projection is kaputt.'));

        try {
            $this->contentRepository->handle(CreateRootWorkspace::create(
                WorkspaceName::forLive(),
                ContentStreamId::create()
            ));
        } catch (\RuntimeException) {
        }

        self::assertEquals(
            SubscriptionStatus::ERROR,
            $this->subscriptionStatus('Vendor.Package:SecondFakeProjection')?->subscriptionStatus
        );

        try {
            $this->crController->statusCommand(contentRepository: $this->contentRepository->id->value, quiet: true);
        } catch (StopCommandException) {
        }
        // exit error code because one projection has a failure
        self::assertEquals(1, $this->response->getExitCode());
        self::assertEmpty($this->bufferedOutput->fetch());

        // repair projection
        $this->secondFakeProjection->killSaboteur();
        $this->subscriptionController->replayCommand(subscription: 'Vendor.Package:SecondFakeProjection', contentRepository: $this->contentRepository->id->value, force: true, quiet: true);

        $this->expectOkayStatus('Vendor.Package:SecondFakeProjection', SubscriptionStatus::ACTIVE, SequenceNumber::fromInteger(2));
    }
}
