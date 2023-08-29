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
use Neos\ContentRepositoryRegistry\Service\NodeMigrationGeneratorService;
use Neos\Flow\Cli\CommandController;
use Neos\ContentRepository\NodeMigration\MigrationException;
use Neos\ContentRepository\NodeMigration\Command\MigrationConfiguration;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\Exception\UnknownPackageException;
use Neos\Flow\Package\PackageManager;
use Neos\Utility\Exception\FilesException;

/**
 * Command controller for tasks related to node migration.
 */
#[Flow\Scope('singleton')]
class NodeMigrationCommandController extends CommandController
{

    public function __construct(
        private readonly MigrationFactory $migrationFactory,
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly ObjectManagerInterface $container,
        private readonly PackageManager $packageManager,
        private readonly NodeMigrationGeneratorService $nodeMigrationGeneratorService
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

            $nodeMigrationService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, new NodeMigrationServiceFactory());
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
     * Creates a node migration for the given package Key.
     *
     * @param string $packageKey The packageKey for the given package
     * @return void
     * @throws UnknownPackageException
     * @throws FilesException
     * @throws StopCommandException
     * @see neos.contentrepositoryregistry:nodemigration:migrationcreate
     */
    public function migrationCreateCommand(string $packageKey): void
    {
       if (!$this->packageManager->isPackageAvailable($packageKey)) {
           $this->outputLine('Package "%s" is not available.', [$packageKey]);
           $this->quit(1);
        }

        try {
            $createdMigration = $this->nodeMigrationGeneratorService->generateBoilerplateMigrationFileInPackage($packageKey);
        } catch (MigrationException $e) {
           $this->outputLine();
           $this->outputLine('Error ' . $e->getMessage());
           $this->quit(1);
        }
       $this->outputLine($createdMigration);
       $this->outputLine('Your node migration has been created successfully.');
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
