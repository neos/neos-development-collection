<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\Command;

use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainerFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Upgrade\ResetupAndReplayContentGraph\ResetupAndReplayContentGraphUpgrade;
use Neos\ContentRepositoryRegistry\Upgrade\EventsRecordedAtToUtc\EventsRecordedAtToUtcUpgrade;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

/**
 * Provides destructive tooling to upgrade the content repository database for a new Neos release.
 *
 * While there is tooling for trivial schema adjustment see "cr:setup" the addition of new db columns without defaults
 * requires adding values inferred by the event stream which is handled by these advanced upgrades.
 *
 * Also rewriting events of the DBAL event-store if deemed required is part of this upgrade tooling.
 *
 * ~ ~ ~ ~ ~ ~ ~
 *
 * Please do ensure you have a backup of your database at hand.
 *
 * ~ ~ ~ ~ ~ ~ ~
 *
 * By convention each upgrade specifies its release date to allow developers to determine if an upgrade is relevant.
 *
 * ~ ~ ~ ~ ~ ~ ~
 *
 * Note regarding further event migrations
 *
 * Up until the last beta 9.0.0-beta20, many migrations were provided via the "migrateevents" Flow command.
 * They can be used to draw inspiration how to write new migrations:
 * {@link https://github.com/neos/neos-development-collection/blob/9.0.0-beta20/Neos.ContentRepositoryRegistry/Classes/Service/EventMigrationService.php}
 *
 */
final class CRUpgradeCommandController extends CommandController
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected CRUpgradeContextFactory $upgradeContextFactory;

    /**
     * Upgrade to allow to empty, set up and replay the graph projection in one step
     *
     * The CR provides a simple setup tooling via "./flow cr:setup" it allows to create the database schemas in the beginning
     * and also minor upgrades from one existing schema to the desired like index changes or small renames.
     *
     * Some Neos versions will include changes which go beyond this as they heavily adjust the schema.
     *
     * - Neos 9.2.0 (June 2026)
     *
     *   - https://github.com/neos/neos-development-collection/pull/5488
     *     - new column workspace.version
     *
     *   - https://github.com/neos/neos-development-collection/pull/5776
     *     - new column hierarchrelation.contentstreamlayer and id
     *     - new table contentstreamlayer
     *
     * - [future version X ...]
     *
     * The following upgrade is required
     *
     *  - 1. Reset (drop old tables),
     *  - 2. Setup (create new empty tables)
     *  - 3. Replay (refill new tables)
     *
     * Attempting to upgrade with "./flow cr:setup" in step 2 without dropping the old content graph tables would fail.
     *
     * Included in June 2026 - part of the minor 9.2.0 release
     *
     * @param string $contentRepository Identifier of the Content Repository to upgrade
     */
    public function resetupAndReplayContentGraphCommand(string $contentRepository = 'default', bool $force = false): void
    {
        $context = $this->contentRepositoryRegistry->buildService(
            ContentRepositoryId::fromString($contentRepository),
            $this->upgradeContextFactory
        );

        if (!$force && !$this->output->askConfirmation(sprintf('> This will completely empty the content graph of content repository "%s" and create the schema from scratch. Afterwards the graph projection will be replayed which will take quite some time. Are you sure to proceed? (y/n) ', $context->contentRepositoryId->value), false)) {
            $this->outputLine('<comment>Abort.</comment>');
            return;
        }

        $upgrade = new ResetupAndReplayContentGraphUpgrade(
            $context,
            $this->output->outputLine(...),
            function () {
                if ($this->output->getProgressBar()->getProgress() === 0) {
                    $this->output->getProgressBar()->setFormat('debug');
                    $this->output->progressStart();
                }
                $this->output->progressAdvance();
            },
            $this->contentRepositoryRegistry->buildService(
                $context->contentRepositoryId,
                new ContentRepositoryMaintainerFactory()
            )
        );

        $upgrade->execute(
            force: $force
        );

        if ($this->output->getProgressBar()->getProgress() !== 0) {
            $this->output->progressFinish();
        }

        $this->outputLine();
    }

    /**
     * Optional upgrade to adjust event time stamps and node dates to UTC
     *
     * https://github.com/neos/neos-development-collection/pull/5716
     *
     * By storing "recordedAt" as datetime field we lost its original timezone information.
     * But we can make the assumption that its timezone should be the same as the one encoded in the ATOM metadata field "initiatingTimeStamp"
     *
     * The upgrade first groups all events by the ATOM offset found in "initiatingTimeStamp".
     * If all events are UTC "+00:00" the upgrade is not necessary. For all non UTC groups we convert the "recordedAt" datetime field
     * to the datetime in the UTC timezone.
     *
     * The upgrade must not be executed multiple times as it would remove the offset to match UTC again for the "recordedAt" datetime even if they are already meant to be UTC.
     * To prevent this from happening we compare the "recordedAt" and "initiatingTimeStamp" and if they are equal considering timezones we know the upgrade was run.
     *
     * Included in June 2026 - part of the bugfix 9.0.13, 9.1.6 and minor 9.2.0 release
     *
     * @param string $contentRepository Identifier of the Content Repository to upgrade
     */
    public function eventsRecordedAtToUtcCommand(string $contentRepository = 'default', bool $force = false): void
    {
        $context = $this->contentRepositoryRegistry->buildService(
            ContentRepositoryId::fromString($contentRepository),
            $this->upgradeContextFactory
        );

        if (!$force && !$this->output->askConfirmation(sprintf('> This will rewrite events of content repository "%s" to use UTC dates consistently and backup the original events. This will take even on big sites less than 5 minutes. To have the UTC changes applied to the graph a replay needs to be done which will take quite some time. Are you sure to proceed? (y/n) ', $context->contentRepositoryId->value), false)) {
            $this->outputLine('<comment>Abort.</comment>');
            return;
        }

        $upgrade = new EventsRecordedAtToUtcUpgrade(
            $context,
            $this->output->outputLine(...)
        );

        $upgrade->execute(
            force: $force
        );
    }
}
