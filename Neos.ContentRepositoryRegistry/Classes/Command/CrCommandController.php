<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Projection\ProjectionStatusType;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Service\ProjectionReplayServiceFactory;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventStore\StatusType;
use Neos\Flow\Cli\CommandController;
use Neos\ContentRepository\Core\Service\ContentStreamPrunerFactory;
use Neos\ContentRepository\Core\Service\WorkspaceMaintenanceServiceFactory;

final class CrCommandController extends CommandController
{

    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly ProjectionReplayServiceFactory $projectionServiceFactory,
    ) {
        parent::__construct();
    }

    /**
     * Sets up and checks required dependencies for a Content Repository instance
     * Like event store and projection database tables.
     *
     * Note: This command is non-destructive, i.e. it can be executed without side effects even if all dependencies are up-to-date
     * Therefore it makes sense to include this command into the Continuous Integration
     *
     * @param string $contentRepository Identifier of the Content Repository to set up
     */
    public function setupCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $this->contentRepositoryRegistry->get($contentRepositoryId)->setup();
        $this->outputLine('<success>Content Repository "%s" was set up</success>', [$contentRepositoryId->value]);
    }

    /**
     * Determine and output the status of the event store and all registered projections for a given Content Repository
     *
     * @param string $contentRepository Identifier of the Content Repository to determine the status for
     */
    public function statusCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $status = $this->contentRepositoryRegistry->get($contentRepositoryId)->status();

        $hasErrorsOrWarnings = false;

        $this->output('Event Store: ');
        $this->outputLine(match ($status->eventStoreStatus->type) {
            StatusType::OK => '<success>OK</success>',
            StatusType::SETUP_REQUIRED => '<comment>Setup required!</comment>',
            StatusType::ERROR => '<error>ERROR</error>',
        });
        if ($status->eventStoreStatus->details !== '') {
            $this->outputFormatted($status->eventStoreStatus->details, [], 2);
        }
        if ($status->eventStoreStatus->type !== StatusType::OK) {
            $hasErrorsOrWarnings = true;
        }
        $this->outputLine();
        foreach ($status->projectionStatuses as $projectionName => $projectionStatus) {
            $this->output('Projection "<b>%s</b>": ', [$projectionName]);
            $this->outputLine(match ($projectionStatus->type) {
                ProjectionStatusType::OK => '<success>OK</success>',
                ProjectionStatusType::SETUP_REQUIRED => '<comment>Setup required!</comment>',
                ProjectionStatusType::REPLAY_REQUIRED => '<comment>Replay required!</comment>',
                ProjectionStatusType::ERROR => '<error>ERROR</error>',
            });
            if ($projectionStatus->type !== ProjectionStatusType::OK) {
                $hasErrorsOrWarnings = true;
            }
            if ($projectionStatus->details !== '') {
                $this->outputFormatted($projectionStatus->details, [], 2);
            }
        }
        if ($hasErrorsOrWarnings) {
            $this->quit(1);
        }
    }

    /**
     * Replays the specified projection of a Content Repository by resetting its state and performing a full catchup
     *
     * @param string $projection Full Qualified Class Name or alias of the projection to replay (e.g. "contentStream")
     * @param string $contentRepository Identifier of the Content Repository instance to operate on
     * @param bool $quiet If set only fatal errors are rendered to the output
     * @param int $until Until which sequence number should projections be replayed? useful for debugging
     */
    public function replayCommand(string $projection, string $contentRepository = 'default', bool $quiet = false, int $until = 0): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $projectionService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, $this->projectionServiceFactory);

        if (!$quiet) {
            $this->outputLine('Replaying events for projection "%s" of Content Repository "%s" ...', [$projection, $contentRepositoryId->value]);
            $this->output->progressStart();
        }
        $options = CatchUpOptions::create(
            progressCallback: fn () => $this->output->progressAdvance(),
        );
        if ($until > 0) {
            $options = $options->with(maximumSequenceNumber: SequenceNumber::fromInteger($until));
        }
        $projectionService->replayProjection($projection, $options);
        if (!$quiet) {
            $this->output->progressFinish();
            $this->outputLine();
            $this->outputLine('<success>Done.</success>');
        }
    }

    /**
     * Replays all projections of the specified Content Repository by resetting their states and performing a full catchup
     *
     * @param string $contentRepository Identifier of the Content Repository instance to operate on
     * @param bool $quiet If set only fatal errors are rendered to the output
     */
    public function replayAllCommand(string $contentRepository = 'default', bool $quiet = false): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $projectionService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, $this->projectionServiceFactory);
        if (!$quiet) {
            $this->outputLine('Replaying events for all projections of Content Repository "%s" ...', [$contentRepositoryId->value]);
            // TODO start progress bar
        }
        $projectionService->replayAllProjections(CatchUpOptions::create());
        if (!$quiet) {
            // TODO finish progress bar
            $this->outputLine('<success>Done.</success>');
        }
    }

    /**
     * This will completely prune the data of the specified content repository.
     *
     * @param string $contentRepository name of the content repository where the data should be pruned from.
     * @return void
     */
    public function pruneCommand(string $contentRepository = 'default'): void
    {
        if (!$this->output->askConfirmation(sprintf("This will prune your content repository \"%s\". Are you sure to proceed? (y/n) ", $contentRepository), false)) {
            return;
        }

        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);

        $contentStreamPruner = $this->contentRepositoryRegistry->buildService(
            $contentRepositoryId,
            new ContentStreamPrunerFactory()
        );
        $contentStreamPruner->pruneAll();

        $workspaceMaintenanceService = $this->contentRepositoryRegistry->buildService(
            $contentRepositoryId,
            new WorkspaceMaintenanceServiceFactory()
        );
        $workspaceMaintenanceService->pruneAll();

        $this->replayAllCommand($contentRepository);
    }
}
