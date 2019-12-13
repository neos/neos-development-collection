<?php
namespace Neos\ContentRepository\Migration\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Cli\CommandController;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Migration\Domain\Factory\MigrationFactory;
use Neos\ContentRepository\Migration\Domain\Repository\MigrationStatusRepository;
use Neos\ContentRepository\Migration\Exception\MigrationException;
use Neos\Flow\Persistence\Doctrine\Exception\DatabaseException;
use Neos\ContentRepository\Migration\Service\NodeMigration;
use Neos\ContentRepository\Migration\Domain\Model\MigrationStatus;
use Neos\ContentRepository\Migration\Domain\Model\MigrationConfiguration;
use Neos\Flow\Annotations as Flow;

/**
 * Command controller for tasks related to node handling.
 *
 * @Flow\Scope("singleton")
 */
class NodeCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var YamlSource
     */
    protected $yamlSourceImporter;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var MigrationStatusRepository
     */
    protected $migrationStatusRepository;

    /**
     * @Flow\Inject
     * @var MigrationFactory
     */
    protected $migrationFactory;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * Do the configured migrations in the given migration.
     *
     * By default the up direction is applied, using the direction parameter this can
     * be changed.
     *
     * @param string $version The version of the migration configuration you want to use.
     * @param boolean $confirmation Confirm application of this migration, only needed if the given migration contains any warnings.
     * @param string $direction The direction to work in, MigrationStatus::DIRECTION_UP or MigrationStatus::DIRECTION_DOWN
     * @return void
     * @see neos.contentrepository.migration:node:migrationstatus
     */
    public function migrateCommand($version, $confirmation = false, $direction = MigrationStatus::DIRECTION_UP)
    {
        try {
            $migrationConfiguration = $direction === MigrationStatus::DIRECTION_UP ?
                $this->migrationFactory->getMigrationForVersion($version)->getUpConfiguration() :
                $this->migrationFactory->getMigrationForVersion($version)->getDownConfiguration();

            $this->outputCommentsAndWarnings($migrationConfiguration);
            if ($migrationConfiguration->hasWarnings() && $confirmation === false) {
                $this->outputLine();
                $this->outputLine('Migration has warnings. You need to confirm execution by adding the "--confirmation true" option to the command.');
                $this->quit(1);
            }

            $nodeMigrationService = new NodeMigration($migrationConfiguration->getMigration());
            $nodeMigrationService->execute();
            $migrationStatus = new MigrationStatus($version, $direction, new \DateTime());
            $this->migrationStatusRepository->add($migrationStatus);
            $this->outputLine();
            $this->outputLine('Successfully applied migration.');
        } catch (MigrationException $e) {
            $this->outputLine();
            $this->outputLine('Error: ' . $e->getMessage());
            $this->quit(1);
        } catch (DatabaseException $exception) {
            $this->outputLine();
            $this->outputLine('An exception occurred during the migration, run a ./flow doctrine:migrate and run the migration again.');
            $this->quit(1);
        }
    }

    /**
     * List available and applied migrations
     *
     * @return void
     * @see neos.contentrepository.migration:node:migrate
     */
    public function migrationStatusCommand()
    {
        $this->outputLine();

        $availableMigrations = $this->migrationFactory->getAvailableMigrationsForCurrentConfigurationType();
        if (count($availableMigrations) === 0) {
            $this->outputLine('No migrations available.');
            $this->quit();
        }

        $appliedMigrations = $this->migrationStatusRepository->findAll();
        $appliedMigrationsDictionary = [];
        /** @var $appliedMigration MigrationStatus */
        foreach ($appliedMigrations as $appliedMigration) {
            $appliedMigrationsDictionary[$appliedMigration->getVersion()][] = $appliedMigration;
        }

        $tableRows = [];
        foreach ($availableMigrations as $version => $migration) {
            $migrationUpConfigurationComments = $this->migrationFactory->getMigrationForVersion($version)->getUpConfiguration()->getComments();

            if (isset($appliedMigrationsDictionary[$version])) {
                $applicationInformation = $this->phraseMigrationApplicationInformation($appliedMigrationsDictionary[$version]);
                if ($applicationInformation !== '') {
                    $migrationUpConfigurationComments .= PHP_EOL . '<b>Applied:</b>' . PHP_EOL . $applicationInformation;
                }
            }

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

    /**
     * @param array $migrationsInVersion
     * @return string
     */
    protected function phraseMigrationApplicationInformation($migrationsInVersion)
    {
        usort($migrationsInVersion, function (MigrationStatus $migrationA, MigrationStatus $migrationB) {
            return $migrationA->getApplicationTimeStamp() > $migrationB->getApplicationTimeStamp();
        });

        $applied = [];
        /** @var MigrationStatus $migrationStatus */
        foreach ($migrationsInVersion as $migrationStatus) {
            $applied[] = sprintf(
                '%s applied on %s',
                str_pad(strtoupper($migrationStatus->getDirection()), 5, ' ', STR_PAD_LEFT),
                $migrationStatus->getApplicationTimeStamp()->format('Y-m-d H:i:s')
            );
        }
        return implode(PHP_EOL, $applied);
    }
}
