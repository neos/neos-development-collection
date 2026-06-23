<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\ResetGraphAndSetup;

use Neos\ContentGraph\DoctrineDbalAdapter\ContentGraphTableNames;
use Neos\ContentRepository\Core\Projection\ProjectionStatusType;
use Neos\ContentRepository\Core\Service\ContentRepositoryMaintainer;
use Neos\ContentRepository\Core\Subscription\ProjectionSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\CRUpgradeContext;
use Neos\ContentRepositoryRegistry\Upgrade\Shared\OutputMessageTrait;

/**
 * Upgrade to allow to empty and set up the graph projection in one step
 *
 * The CR provides a simple setup tooling via "./flow cr:setup" it allows to create the database schemas in the beginning
 * and also minor upgrades from one existing schema to the desired like index changes or small renames.
 *
 * Some Neos versions will include changes which go beyond this as they create columns on the current tables.
 *
 * The following upgrade path is required:
 *
 *  - 1. Reset (drop old tables),
 *  - 2. Setup (create new empty tables)
 *         both done with ./flow crupgrade:resetgraphandsetup
 *
 *  - 3. Replay (refill new tables)
 *         ./flow subscription:replay contentGraph
 *
 * Attempting to upgrade with "./flow cr:setup" in step 2 without dropping the
 * old content graph tables would fail as the columns cannot be added without any values.
 *
 * Included in June 2026 - part of the minor 9.2.0 release
 */
final readonly class ResetGraphAndSetupUpgrade
{
    use OutputMessageTrait;

    public function __construct(
        private CRUpgradeContext $context,
        private \Closure $outputFn,
        private ContentRepositoryMaintainer $contentRepositoryMaintainer,
    ) {
    }

    public function execute(bool $force): void
    {
        if (!$force) {
            $graphProjectionStatus = $this->getGraphProjectionStatus();
            $this->log(
                sprintf('    DEBUG: Content graph projection schema status "%s". Subscription was at sequence number %d with status "%s"' . PHP_EOL, $graphProjectionStatus->setupStatus->type->name, $graphProjectionStatus->subscriptionPosition->value, $graphProjectionStatus->subscriptionStatus->value)
            );
            if ($graphProjectionStatus->setupStatus->type === ProjectionStatusType::OK) {
                $this->log(
                    'Content graph projection is fully set-up. See `flow cr:status`. Not continuing emptying the projection. If you know what you are doing try again by using a bit more force.'
                );
                return;
            }
        }

        $tableNames = ContentGraphTableNames::create($this->context->contentRepositoryId);

        $this->context->dbal->executeStatement('DROP TABLE IF EXISTS ' . $tableNames->node());
        $this->context->dbal->executeStatement('DROP TABLE IF EXISTS ' . $tableNames->hierarchyRelation());
        $this->context->dbal->executeStatement('DROP TABLE IF EXISTS ' . $tableNames->referenceRelation());
        $this->context->dbal->executeStatement('DROP TABLE IF EXISTS ' . $tableNames->dimensionSpacePoints());
        $this->context->dbal->executeStatement('DROP TABLE IF EXISTS ' . $tableNames->workspace());
        $this->context->dbal->executeStatement('DROP TABLE IF EXISTS ' . $tableNames->contentStream());

        $this->log('Dropped all existing graph projection tables.');
        $this->log('Running schema setup ...');

        $result = $this->contentRepositoryMaintainer->setUp();
        if ($result !== null) {
            $this->log(sprintf('Unexpected error during setup: <error>%s</error>', $result->getMessage()));
            return;
        }
        $this->log(sprintf('Content repository "%s" was set up', $this->context->contentRepositoryId->value));
        $this->log('The graph projection now needs to be replayed: `./flow subscription:replay contentGraph`');
    }

    private function getGraphProjectionStatus(): ProjectionSubscriptionStatus
    {
        $status = $this->contentRepositoryMaintainer->status();
        foreach ($status->subscriptionStatus as $status) {
            if ($status instanceof ProjectionSubscriptionStatus) {
                if ($status->subscriptionId->equals(SubscriptionId::fromString('contentGraph'))) {
                    return $status;
                }
            }
        }
        throw new \RuntimeException('Fatal: Status of "contentGraph" is not available but required to.', 1782224867);
    }
}
