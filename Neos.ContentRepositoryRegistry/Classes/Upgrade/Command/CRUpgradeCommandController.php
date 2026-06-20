<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\Command;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Upgrade\EventsDuplicateContentStreamRemoval\EventsDuplicateContentStreamRemovalUpgrade;
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

    /**
     * Migration to deduplicate consecutive base workspace changes
     *
     * https://github.com/neos/neos-development-collection/issues/5877
     *
     * TODO
     *
     * Included in June 2026 - part of the minor 9.2.0 release
     *
     * @param string $contentRepository Identifier of the Content Repository to migrate
     */
    public function eventsDeduplicateBaseWorkspaceChangesCommand(string $contentRepository = 'default', bool $dryRun = false, bool $force = false): void
    {
        if ($dryRun && $force) {
            $this->outputLine('<comment>Abort. Cannot force a dry run;)</comment>');
            return;
        }

        $context = $this->contentRepositoryRegistry->buildService(
            ContentRepositoryId::fromString($contentRepository),
            $this->upgradeContextFactory
        );

        if ((!$dryRun && !$force) && !$this->output->askConfirmation(sprintf('> This will rewrite events of content repository "%s" to remove duplicated base workspace changes and backup the original events. This will take even on big sites less than 5 minutes. Are you sure to proceed? (y/n) ', $context->contentRepositoryId->value), false)) {
            $this->outputLine('<comment>Abort.</comment>');
            return;
        }

        $upgrade = new EventsDuplicateContentStreamRemovalUpgrade(
            $context,
            $this->output->outputLine(...)
        );

        $upgrade->execute(
            dryRun: $dryRun
        );
    }
}
