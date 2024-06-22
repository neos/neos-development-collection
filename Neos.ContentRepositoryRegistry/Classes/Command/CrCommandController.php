<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\Projection\ProjectionStatusType;
use Neos\ContentRepository\Core\Service\ContentStreamPrunerFactory;
use Neos\ContentRepository\Core\Service\WorkspaceMaintenanceServiceFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\RootNodeAggregateDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Exception\InvalidConfigurationException;
use Neos\ContentRepositoryRegistry\Service\ProjectionReplayServiceFactory;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventStore\StatusType;
use Neos\Flow\Cli\CommandController;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Neos\Flow\Persistence\Doctrine\Exception\DatabaseException;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Symfony\Component\Console\Output\Output;

final class CrCommandController extends CommandController
{

    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly ProjectionReplayServiceFactory $projectionServiceFactory,
        private readonly SiteRepository $siteRepository,
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
     * To check if the content repository needs to be setup look into cr:status.
     * That command will also display information what is about to be migrated.
     *
     * @param string $contentRepository Identifier of the Content Repository to set up
     */
    public function setupCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);

        $this->contentRepositoryRegistry->get($contentRepositoryId)->setUp();
        $this->outputLine('<success>Content Repository "%s" was set up</success>', [$contentRepositoryId->value]);
    }

    /**
     * Determine and output the status of the event store and all registered projections for a given Content Repository
     *
     * In verbose mode it will also display information what should and will be migrated when cr:setup is used.
     *
     * @param string $contentRepository Identifier of the Content Repository to determine the status for
     * @param bool $verbose If set, more details will be shown
     * @param bool $quiet If set, no output is generated. This is useful if only the exit code (0 = all OK, 1 = errors or warnings) is of interest
     */
    public function statusCommand(string $contentRepository = 'default', bool $verbose = false, bool $quiet = false): void
    {
        if ($quiet) {
            $this->output->getOutput()->setVerbosity(Output::VERBOSITY_QUIET);
        }
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $status = $this->contentRepositoryRegistry->get($contentRepositoryId)->status();

        $this->output('Event Store: ');
        $this->outputLine(match ($status->eventStoreStatus->type) {
            StatusType::OK => '<success>OK</success>',
            StatusType::SETUP_REQUIRED => '<comment>Setup required!</comment>',
            StatusType::ERROR => '<error>ERROR</error>',
        });
        if ($verbose && $status->eventStoreStatus->details !== '') {
            $this->outputFormatted($status->eventStoreStatus->details, [], 2);
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
            if ($verbose && ($projectionStatus->type !== ProjectionStatusType::OK || $projectionStatus->details)) {
                $lines = explode(chr(10), $projectionStatus->details ?: '<comment>No details available.</comment>');
                foreach ($lines as $line) {
                    $this->outputLine('  ' . $line);
                }
                $this->outputLine();
            }
        }
        if (!$status->isOk()) {
            $this->quit(1);
        }
    }

    /**
     * Replays the specified projection of a Content Repository by resetting its state and performing a full catchup.
     *
     * @param string $projection Full Qualified Class Name or alias of the projection to replay (e.g. "contentStream")
     * @param string $contentRepository Identifier of the Content Repository instance to operate on
     * @param bool $force Replay the projection without confirmation. This may take some time!
     * @param bool $quiet If set only fatal errors are rendered to the output (must be used with --force flag to avoid user input)
     * @param int $until Until which sequence number should projections be replayed? useful for debugging
     */
    public function projectionReplayCommand(string $projection, string $contentRepository = 'default', bool $force = false, bool $quiet = false, int $until = 0): void
    {
        if ($quiet) {
            $this->output->getOutput()->setVerbosity(Output::VERBOSITY_QUIET);
        }
        $progressBar = new ProgressBar($this->output->getOutput());
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:16s%/%estimated:-16s% %memory:6s%');
        if (!$force && $quiet) {
            $this->outputLine('Cannot run in quiet mode without --force. Please acknowledge that this command will reset and replay this projection. This may take some time.');
            $this->quit(1);
        }

        if (!$force && !$this->output->askConfirmation(sprintf('> This will replay the projection "%s" in "%s", which may take some time. Are you sure to proceed? (y/n) ', $projection, $contentRepository), false)) {
            $this->outputLine('<comment>Abort.</comment>');
            return;
        }

        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $projectionService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, $this->projectionServiceFactory);

        $options = CatchUpOptions::create();
        if (!$quiet) {
            $this->outputLine('Replaying events for projection "%s" of Content Repository "%s" ...', [$projection, $contentRepositoryId->value]);
            $progressBar->start(max($until > 0 ? $until : $projectionService->highestSequenceNumber()->value, 1));
            $options->with(progressCallback: fn () => $progressBar->advance());
        }
        if ($until > 0) {
            $options = $options->with(maximumSequenceNumber: SequenceNumber::fromInteger($until));
        }
        $projectionService->replayProjection($projection, $options);
        if (!$quiet) {
            $progressBar->finish();
            $this->outputLine();
            $this->outputLine('<success>Done.</success>');
        }
    }

    /**
     * Replays all projections of the specified Content Repository by resetting their states and performing a full catchup
     *
     * @param string $contentRepository Identifier of the Content Repository instance to operate on
     * @param bool $force Replay the projection without confirmation. This may take some time!
     * @param bool $quiet If set only fatal errors are rendered to the output (must be used with --force flag to avoid user input)
     * @param int $until Until which sequence number should projections be replayed? useful for debugging
     */
    public function projectionReplayAllCommand(string $contentRepository = 'default', bool $force = false, bool $quiet = false, int $until = 0): void
    {
        if ($quiet) {
            $this->output->getOutput()->setVerbosity(Output::VERBOSITY_QUIET);
        }
        $mainSection = ($this->output->getOutput() instanceof ConsoleOutput) ? $this->output->getOutput()->section() : $this->output->getOutput();
        $mainProgressBar = new ProgressBar($mainSection);
        $mainProgressBar->setBarCharacter('<success>█</success>');
        $mainProgressBar->setEmptyBarCharacter('░');
        $mainProgressBar->setProgressCharacter('<success>█</success>');
        $mainProgressBar->setFormat('debug');

        $subSection = ($this->output->getOutput() instanceof ConsoleOutput) ? $this->output->getOutput()->section() : $this->output->getOutput();
        $progressBar = new ProgressBar($subSection);
        $progressBar->setFormat(' %message% - %current%/%max% [%bar%] %percent:3s%% %elapsed:16s%/%estimated:-16s% %memory:6s%');
        if (!$force && $quiet) {
            $this->outputLine('Cannot run in quiet mode without --force. Please acknowledge that this command will reset and replay this projection. This may take some time.');
            $this->quit(1);
        }

        if (!$force && !$this->output->askConfirmation(sprintf('> This will replay all projections in "%s", which may take some time. Are you sure to proceed? (y/n) ', $contentRepository), false)) {
            $this->outputLine('<comment>Abort.</comment>');
            return;
        }

        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $projectionService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, $this->projectionServiceFactory);
        if (!$quiet) {
            $this->outputLine('Replaying events for all projections of Content Repository "%s" ...', [$contentRepositoryId->value]);
        }
        $options = CatchUpOptions::create();
        if (!$quiet) {
            $options = $options->with(progressCallback: fn () => $progressBar->advance());
        }
        if ($until > 0) {
            $options = $options->with(maximumSequenceNumber: SequenceNumber::fromInteger($until));
        }
        $highestSequenceNumber = max($until > 0 ? $until : $projectionService->highestSequenceNumber()->value, 1);
        $mainProgressBar->start($projectionService->numberOfProjections());
        $mainProgressCallback = null;
        if (!$quiet) {
            $mainProgressCallback = static function (string $projectionAlias) use ($mainProgressBar, $progressBar, $highestSequenceNumber) {
                $mainProgressBar->advance();
                $progressBar->setMessage($projectionAlias);
                $progressBar->start($highestSequenceNumber);
                $progressBar->setProgress(0);
            };
        }
        $projectionService->replayAllProjections($options, $mainProgressCallback);
        if (!$quiet) {
            $mainProgressBar->finish();
            $progressBar->finish();
            $this->outputLine('<success>Done.</success>');
        }
    }

    /**
     * This will completely prune the data of the specified content repository.
     *
     * @param string $contentRepository Name of the content repository where the data should be pruned from.
     * @param bool $force Prune the cr without confirmation. This cannot be reverted!
     * @return void
     */
    public function pruneCommand(string $contentRepository = 'default', bool $force = false): void
    {
        if (!$force && !$this->output->askConfirmation(sprintf('> This will prune your content repository "%s". Are you sure to proceed? (y/n) ', $contentRepository), false)) {
            $this->outputLine('<comment>Abort.</comment>');
            return;
        }

        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);

        $contentStreamPruner = $this->contentRepositoryRegistry->buildService(
            $contentRepositoryId,
            new ContentStreamPrunerFactory()
        );

        $workspaceMaintenanceService = $this->contentRepositoryRegistry->buildService(
            $contentRepositoryId,
            new WorkspaceMaintenanceServiceFactory()
        );

        $projectionService = $this->contentRepositoryRegistry->buildService(
            $contentRepositoryId,
            $this->projectionServiceFactory
        );

        // reset the events table
        $contentStreamPruner->pruneAll();
        $workspaceMaintenanceService->pruneAll();
        // reset the projections state
        $projectionService->resetAllProjections();

        $this->outputLine('<success>Done.</success>');
    }

    /**
     * @param bool $verbose shows additional internal output regarding content-streams and nodes in the projection
     */
    public function listCommand(bool $verbose = false): void
    {
        /** @var list<Site> $neosSiteEntities */
        $neosSiteEntities = [];
        try {
            $neosSiteEntities = iterator_to_array($this->siteRepository->findAll());
        } catch (DatabaseException) {
            // doctrine might have not been migrated yet or no database is connected.
            $this->outputLine('<comment>Site repository is not accessible.</comment>');
        }

        $rows = [];
        foreach ($this->contentRepositoryRegistry->getContentRepositoryIds() as $contentRepositoryId) {
            $contentRepository = null;
            try {
                $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
            } catch (InvalidConfigurationException $exception) {
                $this->outputLine('<comment>Content repository %s is not well configures: %s.</comment>', [$contentRepositoryId->value, $exception->getMessage()]);
            }


            $liveContentGraph = null;
            try {
                $liveContentGraph = $contentRepository->getContentGraph(WorkspaceName::forLive());
            } catch (WorkspaceDoesNotExist) {
                $this->outputLine('<comment>Live workspace in content repository %s not existing.</comment>', [$contentRepositoryId->value]);
            }

            $currenSiteNodes = [];
            // todo wrap in catch runtime exception
            if ($liveContentGraph && $verbose) {
                $sitesAggregate = null;
                try {
                    $sitesAggregate = $liveContentGraph->findRootNodeAggregateByType(NodeTypeNameFactory::forSites());
                } catch (RootNodeAggregateDoesNotExist) {
                    $this->outputLine('<comment>Sites root node does not exist in content repository %s.</comment>', [$contentRepositoryId->value]);
                }

                if ($sitesAggregate) {
                    $siteNodeAggregates = $liveContentGraph->findChildNodeAggregates($sitesAggregate->nodeAggregateId);
                    foreach ($siteNodeAggregates as $siteNodeAggregate) {
                        $currenSiteNodes[] = $siteNodeAggregate->nodeName->value;
                    }
                }
            }

            $currentNeosSiteEntities = [];
            foreach ($neosSiteEntities as $site) {
                if (!$site->getConfiguration()->contentRepositoryId->equals($contentRepositoryId)) {
                    continue;
                }
                $currentNeosSiteEntities[] = $site->getNodeName()->value;
            }

            if ($verbose) {
                $connectedWorkingSites = array_intersect($currentNeosSiteEntities, $currenSiteNodes);
                $siteNodesWithoutMatchingNeosSiteEntity = array_diff($currenSiteNodes, $currentNeosSiteEntities);
                $neosSiteEntitiesWithoutMatchingSiteNode = array_diff($currentNeosSiteEntities, $currenSiteNodes);
                $sitesString = ltrim(
                    (join(', ', $connectedWorkingSites)
                        . ($siteNodesWithoutMatchingNeosSiteEntity ? (', (only node: ' . join(', ', $siteNodesWithoutMatchingNeosSiteEntity) . ')') : '')
                        . ($neosSiteEntitiesWithoutMatchingSiteNode ? (', (only neos site: ' . join(', ', $neosSiteEntitiesWithoutMatchingSiteNode) . ')') : '')
                    ), ' ,') ?: '-';
            } else {
                $sitesString = join(', ', $currentNeosSiteEntities) ?: '-';
            }

            $statusString = '-';
            $workspacesString = '-';
            $contentStreamsString = '-';
            $nodesString = '-';

            if ($contentRepository) {
                $statusString = $contentRepository->status()->isOk() ? 'okay' : 'not okay';

                try {
                    $workspacesString = sprintf('%d found', count($contentRepository->getWorkspaceFinder()->findAll()));
                } catch (\RuntimeException $e) {
                    $this->outputLine('<comment>WorkspaceFinder of %s not functional: %s.</comment>', [$contentRepositoryId->value, $e->getMessage()]);
                }

                if ($verbose) {
                    try {
                        $contentStreamsString = sprintf('%d found', iterator_count($contentRepository->getContentStreamFinder()->findAllIds()));
                    } catch (\RuntimeException $e) {
                        $this->outputLine('<comment>ContentStreamFinder of %s not functional: %s.</comment>', [$contentRepositoryId->value, $e->getMessage()]);
                    }

                    try {
                        if ($liveContentGraph) {
                            $nodesString = sprintf('%d found', $liveContentGraph->countNodes());
                        }
                    } catch (\RuntimeException $e) {
                        $this->outputLine('<comment>ContentGraph of %s not functional: %s.</comment>', [$contentRepositoryId->value, $e->getMessage()]);
                    }
                }
            }

            $rows[] = [
                $contentRepositoryId->value,
                $statusString,
                $sitesString,
                $workspacesString,
                ...($verbose ? [$contentStreamsString, $nodesString] : [])
            ];
        }

        $this->output->outputTable($rows, ['Identifier', 'Status', 'Sites', 'Workspaces', ...($verbose ? ['Contentstreams', 'Nodes'] : [])]);
    }
}
