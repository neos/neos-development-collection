<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

use Neos\ContentRepository\Core\Projection\ProjectionStatusType;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainerFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\Subscription\DetachedSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\ProjectionSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\EventStore\Model\EventStore\StatusType;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Symfony\Component\Console\Output\Output;

/**
 * Set up a content repository
 *
 * *Initialisation*
 *
 * The command "./flow cr:setup" sets up the content repository like event store and subscription database tables.
 * It is non-destructive.
 *
 * Note that a reset is not implemented here but for the Neos CMS use-case provided via "./flow site:pruneAll"
 *
 * *Staus information*
 *
 * The status of the content repository e.g. if a setup is required or if all subscriptions are active and their position
 * can be examined with "./flow cr:status"
 *
 * See also {@see ContentRepositoryMaintainer} for more information.
 */
final class CrCommandController extends CommandController
{
    #[Flow\Inject()]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

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
     * @param bool $quiet If set, no output is generated. This is useful if only the exit code (0 = all OK, 1 = errors or warnings) is of interest
     * @param string $contentRepository Identifier of the Content Repository to set up
     */
    public function setupCommand(string $contentRepository = 'default', bool $quiet = false): void
    {
        if ($quiet) {
            $this->output->getOutput()->setVerbosity(Output::VERBOSITY_QUIET);
        }
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $contentRepositoryMaintainer = $this->contentRepositoryRegistry->buildService($contentRepositoryId, new ContentRepositoryMaintainerFactory());

        $result = $contentRepositoryMaintainer->setUp();
        if ($result !== null) {
            $this->outputLine('<error>%s</error>', [$result->getMessage()]);
            $this->quit(1);
        }
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
        $contentRepositoryMaintainer = $this->contentRepositoryRegistry->buildService($contentRepositoryId, new ContentRepositoryMaintainerFactory());
        $crStatus = $contentRepositoryMaintainer->status();
        $hasErrors = false;
        $replayRequired = false;
        $setupRequired = false;
        $this->outputLine('Event Store:');
        $this->output('  Setup: ');
        $this->outputLine(match ($crStatus->eventStoreStatus->type) {
            StatusType::OK => '<success>OK</success>',
            StatusType::SETUP_REQUIRED => '<comment>Setup required!</comment>',
            StatusType::ERROR => '<error>ERROR</error>',
        });
        if ($crStatus->eventStorePosition) {
            $this->outputLine('  Position: %d', [$crStatus->eventStorePosition->value]);
        } else {
            $this->outputLine('  Position: <error>Loading failed!</error>');
        }
        $hasErrors |= $crStatus->eventStoreStatus->type === StatusType::ERROR;
        if ($verbose && $crStatus->eventStoreStatus->details !== '') {
            $this->outputFormatted($crStatus->eventStoreStatus->details, [], 2);
        }
        $this->outputLine();
        $this->outputLine('Subscriptions:');
        if ($crStatus->subscriptionStatus->isEmpty()) {
            $this->outputLine('<error>There are no registered subscriptions yet, please run <em>./flow cr:setup</em></error>');
            $this->quit(1);
        }
        foreach ($crStatus->subscriptionStatus as $status) {
            if ($status instanceof DetachedSubscriptionStatus) {
                $this->outputLine('  <b>%s</b>:', [$status->subscriptionId->value]);
                $this->output('    Subscription: ');
                $this->output('%s <comment>DETACHED</comment>', [$status->subscriptionId->value, $status->subscriptionStatus === SubscriptionStatus::DETACHED ? 'is' : 'will be']);
                $this->outputLine(' at position <b>%d</b>', [$status->subscriptionPosition->value]);
            }
            if ($status instanceof ProjectionSubscriptionStatus) {
                $this->outputLine('  <b>%s</b>:', [$status->subscriptionId->value]);
                $this->output('    Setup: ');
                $this->outputLine(match ($status->setupStatus->type) {
                    ProjectionStatusType::OK => '<success>OK</success>',
                    ProjectionStatusType::SETUP_REQUIRED => '<comment>SETUP REQUIRED</comment>',
                    ProjectionStatusType::ERROR => '<error>ERROR</error>',
                });
                $hasErrors |= $status->setupStatus->type === ProjectionStatusType::ERROR;
                $setupRequired |= $status->setupStatus->type === ProjectionStatusType::SETUP_REQUIRED;
                if ($verbose && ($status->setupStatus->type !== ProjectionStatusType::OK || $status->setupStatus->details)) {
                    $lines = explode(chr(10), $status->setupStatus->details ?: '<comment>No details available.</comment>');
                    foreach ($lines as $line) {
                        $this->outputLine('      ' . $line);
                    }
                    $this->outputLine();
                }
                $this->output('    Projection: ');
                $this->output(match ($status->subscriptionStatus) {
                    SubscriptionStatus::NEW => '<comment>NEW</comment>',
                    SubscriptionStatus::BOOTING => '<comment>BOOTING</comment>',
                    SubscriptionStatus::ACTIVE => '<success>ACTIVE</success>',
                    SubscriptionStatus::DETACHED => '<comment>DETACHED</comment>',
                    SubscriptionStatus::ERROR => '<error>ERROR</error>',
                });
                if ($crStatus->eventStorePosition?->value > $status->subscriptionPosition->value) {
                    // projection is behind
                    $this->outputLine(' at position <error>%d</error>', [$status->subscriptionPosition->value]);
                } else {
                    $this->outputLine(' at position <b>%d</b>', [$status->subscriptionPosition->value]);
                }
                $hasErrors |= $status->subscriptionStatus === SubscriptionStatus::ERROR;
                $replayRequired |= $status->subscriptionStatus === SubscriptionStatus::ERROR;
                $replayRequired |= $status->subscriptionStatus === SubscriptionStatus::BOOTING;
                $replayRequired |= $status->subscriptionStatus === SubscriptionStatus::DETACHED;
                if ($verbose && $status->subscriptionError !== null) {
                    $lines = explode(chr(10), $status->subscriptionError->errorMessage ?: '<comment>No details available.</comment>');
                    foreach ($lines as $line) {
                        $this->outputLine('<error>      %s</error>', [$line]);
                    }
                }
            }
        }
        if ($verbose) {
            $this->outputLine();
            if ($setupRequired) {
                $this->outputLine('<comment>Setup required, please run <em>./flow cr:setup</em></comment>');
            }
            if ($replayRequired) {
                $this->outputLine('<comment>Replay needed for <comment>BOOTING</comment>, <comment>ERROR</comment> or <comment>DETACHED</comment> subscriptions, please run <em>./flow subscription:replay [subscription-id]</em></comment>');
            }
        }
        if ($hasErrors) {
            $this->quit(1);
        }
    }
}
