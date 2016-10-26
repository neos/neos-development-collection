<?php
namespace TYPO3\TYPO3CR\Migration\Command;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Configuration\Source\YamlSource;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Migration\Domain\Factory\MigrationFactory;
use TYPO3\TYPO3CR\Migration\Domain\Repository\MigrationStatusRepository;
use TYPO3\TYPO3CR\Migration\Exception\MigrationException;
use TYPO3\Flow\Persistence\Doctrine\Exception\DatabaseException;
use TYPO3\TYPO3CR\Migration\Service\NodeMigration;
use TYPO3\TYPO3CR\Migration\Domain\Model\MigrationStatus;
use TYPO3\TYPO3CR\Migration\Domain\Model\MigrationConfiguration;
use TYPO3\Flow\Annotations as Flow;

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
                $this->outputLine('Migration has warnings. You need to confirm execution by adding the "--confirmation TRUE" option to the command.');
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
     * @see typo3.typo3cr.migration:node:listavailablemigrations
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
        $appliedMigrationsDictionary = array();
        /** @var $appliedMigration MigrationStatus */
        foreach ($appliedMigrations as $appliedMigration) {
            $appliedMigrationsDictionary[$appliedMigration->getVersion()][] = $appliedMigration;
        }

        $tableRows = array();
        foreach ($availableMigrations as $version => $migration) {
            $migrationUpConfigurationComments = $this->migrationFactory->getMigrationForVersion($version)->getUpConfiguration()->getComments();

            if (isset($appliedMigrationsDictionary[$version])) {
                $applicationInformation = $this->phraseMigrationApplicationInformation($appliedMigrationsDictionary[$version]);
                if ($applicationInformation !== '') {
                    $migrationUpConfigurationComments .= PHP_EOL . '<b>Applied:</b>' . PHP_EOL . $applicationInformation;
                }
            }

            $tableRows[] = array(
                $version,
                $migration['formattedVersionNumber'],
                $migration['package']->getPackageKey(),
                wordwrap($migrationUpConfigurationComments, 60)
            );
        }

        $this->outputLine('<b>Available migrations</b>');
        $this->outputLine();
        $this->output->outputTable($tableRows, array('Version', 'Date', 'Package', 'Comments'));
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
            $this->outputFormatted($migrationConfiguration->getComments(), array(), 2);
        }

        if ($migrationConfiguration->hasWarnings()) {
            $this->outputLine();
            $this->outputLine('<b><u>Warnings</u></b>');
            $this->outputFormatted($migrationConfiguration->getWarnings(), array(), 2);
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

        $applied = array();
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
