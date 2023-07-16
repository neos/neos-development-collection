<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Service\EventMigrationServiceFactory;
use Neos\Flow\Cli\CommandController;

final class MigrateEventsCommandController extends CommandController
{

    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly EventMigrationServiceFactory $eventMigrationServiceFactory,
    ) {
        parent::__construct();
    }

    /**
     * Adds affectedDimensionSpacePoints to NodePropertiesWereSet event, by replaying the content graph
     * and then reading the dimension space points for the relevant NodeAggregate.
     *
     * Needed for #4265: https://github.com/neos/neos-development-collection/issues/4265
     *
     * Included in May 2023 - before Neos 9.0 Beta 1.
     *
     * @param string $contentRepository Identifier of the Content Repository to set up
     */
    public function fillAffectedDimensionSpacePointsInNodePropertiesWereSetCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $eventMigrationService = $this->contentRepositoryRegistry->getService($contentRepositoryId, $this->eventMigrationServiceFactory);
        $eventMigrationService->fillAffectedDimensionSpacePointsInNodePropertiesWereSet($this->outputLine(...));
    }
}
