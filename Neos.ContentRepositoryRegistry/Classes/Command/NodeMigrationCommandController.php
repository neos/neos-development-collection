<?php

namespace Neos\ContentRepositoryRegistry\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\NodeMigration\NodeMigrationServiceFactory;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\NodeMigration\Command\ExecuteMigration;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Migration\Factory\MigrationFactory;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\Flow\Cli\CommandController;
use Neos\ContentRepository\NodeMigration\MigrationException;
use Neos\ContentRepository\NodeMigration\Command\MigrationConfiguration;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * Command controller for tasks related to node migration.
 */
#[Flow\Scope('singleton')]
class NodeMigrationCommandController extends CommandController
{

    public function __construct(
        private readonly MigrationFactory $migrationFactory,
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly ObjectManagerInterface $container
    )
    {
        parent::__construct();
    }

    /**
     * Do the configured migrations in the given migration.
     *
     * @param string $version The version of the migration configuration you want to use.
     * @param string $workspace The workspace where the migration should be applied; by default "live"
     * @param boolean $force Confirm application of this migration,
     *                       only needed if the given migration contains any warnings.
     * @return void
     * @throws \Neos\Flow\Cli\Exception\StopCommandException
     * @see neos.contentrepository.migration:node:migrationstatus
     */
    public function migrateCommand(string $version, $workspace = 'live', bool $force = false, string $contentRepositoryIdentifier = 'default')
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryIdentifier);

        try {
            $migrationConfiguration = $this->migrationFactory->getMigrationForVersion($version);

            $this->outputCommentsAndWarnings($migrationConfiguration);
            if ($migrationConfiguration->hasWarnings() && $force === false) {
                $this->outputLine();
                $this->outputLine('Migration has warnings.'
                    . ' You need to confirm execution by adding the "--confirmation true" option to the command.');
                $this->quit(1);
            }

            $nodeMigrationService = $this->contentRepositoryRegistry->getService($contentRepositoryId, new NodeMigrationServiceFactory());
            $nodeMigrationService->executeMigration(
                new ExecuteMigration(
                    $migrationConfiguration,
                    WorkspaceName::fromString($workspace)
                )
            );
            $this->outputLine();
            $this->outputLine('Successfully applied migration.');
        } catch (MigrationException $e) {
            $this->outputLine();
            $this->outputLine('Error: ' . $e->getMessage());
            $this->quit(1);
        }
    }

    /**
     * Helper to output comments and warnings for the given configuration.
     *
     * @param MigrationConfiguration $migrationConfiguration
     * @return void
     */
    protected function outputCommentsAndWarnings(MigrationConfiguration $migrationConfiguration)
    {
        if ($migrationConfiguration->hasComments()) {
            $this->outputLine();
            $this->outputLine('<b>Comments</b>');
            $this->outputFormatted($migrationConfiguration->getComments(), [], 2);
        }

        if ($migrationConfiguration->hasWarnings()) {
            $this->outputLine();
            $this->outputLine('<b><u>Warnings</u></b>');
            $this->outputFormatted($migrationConfiguration->getWarnings(), [], 2);
        }
    }
}
