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

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\NodeMigration\Command\ExecuteMigration;
use Neos\ContentRepository\NodeMigration\Command\MigrationConfiguration;
use Neos\ContentRepository\NodeMigration\MigrationException;
use Neos\ContentRepository\NodeMigration\NodeMigrationServiceFactory;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Migration\Factory\MigrationFactory;
use Neos\ContentRepositoryRegistry\Service\NodeMigrationGeneratorService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\Exception\StopCommandException;
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
        private readonly PackageManager $packageManager,
        private readonly NodeMigrationGeneratorService $nodeMigrationGeneratorService
    ) {
        parent::__construct();
    }

    /**
     * Do the configured migrations in the given migration.
     *
     * @param string $version The version of the migration configuration you want to use.
     * @param string $sourceWorkspace The workspace where the migration should be applied; by default "live"
     * @param bool $publishOnSuccess If true, the changes get published automatically after successful apply (default: true).
     * @param boolean $force Confirm application of this migration, only needed if the given migration contains any warnings.
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     * @return void
     * @throws StopCommandException
     * @see neos.contentrepositoryregistry:nodemigration:execute
     */
    public function executeCommand(string $version, string $sourceWorkspace = 'live', bool $publishOnSuccess = true, bool $force = false, string $contentRepository = 'default'): void
    {
        $sourceWorkspaceName = WorkspaceName::fromString($sourceWorkspace);
        $targetWorkspaceName = WorkspaceName::transliterateFromString(sprintf('migration-%s-%s', $version, $sourceWorkspaceName->value));
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);

        try {
            $migrationConfiguration = $this->migrationFactory->getMigrationForVersion($version);

            $this->outputCommentsAndWarnings($migrationConfiguration);
            if ($migrationConfiguration->hasWarnings() && $force === false) {
                $this->outputLine();
                $this->outputLine('Migration has warnings.'
                    . ' You need to confirm execution by adding the "--force true" option to the command.');
                $this->quit(1);
            }

            $nodeMigrationService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, new NodeMigrationServiceFactory());
            $nodeMigrationService->executeMigration(
                new ExecuteMigration(
                    $migrationConfiguration,
                    $sourceWorkspaceName,
                    $targetWorkspaceName,
                    $publishOnSuccess,
                    ContentStreamId::create()
                )
            );

            $this->outputLine();
            $this->outputLine('Successfully applied migration.');
            if ($publishOnSuccess) {
                $this->outputLine('You should rebase all outdated workspaces to ensure every workspace get the changes immediately. `./flow workspace:rebaseoutdated`');
            } else {
                $this->outputLine(sprintf('We created a workspace "%s" for review. Please review changes an publish them to "%s".', $targetWorkspaceName->value, $sourceWorkspaceName->value));
                $this->outputLine('You should rebase all outdated workspaces after publishing to ensure every workspace get the changes immediately. `./flow workspace:rebaseoutdated`');
            }

        } catch (MigrationException $e) {
            $this->outputLine();
            $this->outputLine('Error on applying node migrations:');
            $this->outputLine($e->getMessage());
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
     * @see neos.contentrepositoryregistry:nodemigration:kickstart
     */
    public function kickstartCommand(string $packageKey): void
    {
        if (!$this->packageManager->isPackageAvailable($packageKey)) {
            $this->outputLine('Package "%s" is not available.', [$packageKey]);
            $this->quit(1);
        }

        $createdMigration = $this->nodeMigrationGeneratorService->generateBoilerplateMigrationFileInPackage($packageKey);
        $this->outputLine($createdMigration);
        $this->outputLine('Your node migration has been created successfully.');
    }

    /**
     * List available migrations
     *
     * @see neos.contentrepositoryregistry:nodemigration:list
     */
    public function listCommand(): void
    {
        $availableMigrations = $this->migrationFactory->getAvailableVersions();
        if (count($availableMigrations) === 0) {
            $this->outputLine('No migrations available.');
            $this->quit();
        }

        $tableRows = [];
        foreach ($availableMigrations as $version => $migration) {
            $migrationUpConfigurationComments = $this->migrationFactory->getMigrationForVersion($version)->getComments();

            $tableRows[] = [
                $version,
                $migration['formattedVersionNumber'],
                $migration['package']->getPackageKey(),

                wordwrap($migrationUpConfigurationComments, 60)
            ];
        }

        $this->outputLine('<b>Available migrations</b>');
        $this->outputLine();
        $this->output->outputTable($tableRows, ['Version', 'Date', 'Package', 'Comments']);
    }

    /**
     * Helper to output comments and warnings for the given configuration.
     *
     * @param MigrationConfiguration $migrationConfiguration
     * @return void
     */
    protected function outputCommentsAndWarnings(MigrationConfiguration $migrationConfiguration): void
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
