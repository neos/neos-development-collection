<?php
namespace TYPO3\TYPO3CR\Migration\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\Migration\Service\NodeMigration;
use TYPO3\TYPO3CR\Migration\Domain\Model\MigrationStatus;
use TYPO3\TYPO3CR\Migration\Domain\Model\MigrationConfiguration;
use TYPO3\Flow\Annotations as Flow;

/**
 * Command controller for tasks related to node handling.
 *
 * @Flow\Scope("singleton")
 */
class NodeCommandController extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Configuration\Source\YamlSource
	 */
	protected $yamlSourceImporter;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Migration\Domain\Repository\MigrationStatusRepository
	 */
	protected $migrationStatusRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Migration\Domain\Factory\MigrationFactory
	 */
	protected $migrationFactory;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * Do the configured migrations in the given migration file for the given workspace
	 *
	 * By default the up direction is applied, using the direction parameter this can
	 * be changed.
	 *
	 * @param string $workspace The name of the workspace you want to migrate. This workspace must exist.
	 * @param string $version The version of the migration configuration you want to use.
	 * @param boolean $confirmation Confirm application of this migration, only needed if the given migration contains any warnings.
	 * @param string $direction The direction to work in, MigrationStatus::DIRECTION_UP or MigrationStatus::DIRECTION_DOWN
	 * @return void
	 */
	public function migrateCommand($workspace, $version, $confirmation = FALSE, $direction = MigrationStatus::DIRECTION_UP) {
		$context = $this->prepareContext($workspace);
		$migrationConfiguration = $direction === MigrationStatus::DIRECTION_UP ?
			$this->migrationFactory->getMigrationForVersion($version)->getUpConfiguration() :
			$this->migrationFactory->getMigrationForVersion($version)->getDownConfiguration();

		$this->outputCommentsAndWarnings($migrationConfiguration);
		if ($migrationConfiguration->hasWarnings() && $confirmation === FALSE) {
			$this->outputLine();
			$this->outputLine('Migration has warnings. You need to confirm execution by adding the "--confirmation TRUE" option to the command.');
			$this->quit(1);
		}

		$nodeMigrationService = new NodeMigration($context, $migrationConfiguration->getMigration());
		switch ($direction) {
			case MigrationStatus::DIRECTION_UP:
				$nodeMigrationService->migrateUp();
			break;
			case MigrationStatus::DIRECTION_DOWN:
				$nodeMigrationService->migrateDown();
			break;
			default:

		}
		$migrationStatus = new MigrationStatus($version, $workspace, $direction, new \DateTime());
		$this->migrationStatusRepository->add($migrationStatus);
		$this->outputLine();
		$this->outputLine('Successfully applied migration.');
	}

	/**
	 * List available and applied migrations
	 *
	 * @param string $workspace
	 * @return void
	 * @see typo3.typo3cr.migration:node:listavailablemigrations
	 */
	public function migrationStatusCommand($workspace = NULL) {
		/** @var $appliedMigration MigrationStatus */
		$this->outputLine();
		if ($workspace !== NULL) {
			$appliedMigrations = $this->migrationStatusRepository->findByWorkspaceName($workspace);
		} else {
			$appliedMigrations = $this->migrationStatusRepository->findAll();
		}
		$appliedMigrationsDictionary = array();
		foreach ($appliedMigrations as $appliedMigration) {
			$appliedMigrationsDictionary[$appliedMigration->getVersion()][] = $appliedMigration;
		}

		$availableMigrations = $this->migrationFactory->getAvailableMigrationsForCurrentConfigurationType();
		if (count($availableMigrations) > 0) {
			$this->outputLine('<b>Available migrations</b>');
			$this->outputLine();
			foreach ($availableMigrations as $version => $migration) {
				$this->outputLine($version . '   ' . $migration['formattedVersionNumber'] . '   ' . $migration['package']->getPackageKey());

				if (isset($appliedMigrationsDictionary[$version])) {
					$migrationsInVersion = $appliedMigrationsDictionary[$version];
					usort($migrationsInVersion, function(MigrationStatus $migrationA, MigrationStatus $migrationB) {
						return $migrationA->getApplicationTimeStamp() > $migrationB->getApplicationTimeStamp();
					});
					foreach ($migrationsInVersion as $appliedMigration) {
						$this->outputFormatted('%s applied on %s to workspace "%s"',
							array(
								str_pad(strtoupper($appliedMigration->getDirection()), 4, ' ', STR_PAD_LEFT),
								$appliedMigration->getApplicationTimeStamp()->format('d-m-Y H:i:s'),
								$appliedMigration->getWorkspaceName()
							),
							2
						);
						$this->outputLine();
					}
				}
			}
		} else {
			$this->outputLine('No migrations available.');
		}
	}

	/**
	 * Helper to output comments and warnings for the given configuration.
	 *
	 * @param \TYPO3\TYPO3CR\Migration\Domain\Model\MigrationConfiguration $migrationConfiguration
	 * @return void
	 */
	protected function outputCommentsAndWarnings(MigrationConfiguration $migrationConfiguration) {
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
	 * Creates an appropriately configured Context instance for the given
	 * workspace and sets it on the used node repository.
	 *
	 * @param string $workspaceName
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected function prepareContext($workspaceName) {
		$contextProperties = array(
			'workspaceName' => $workspaceName
		);

		return $this->contextFactory->create($contextProperties);
	}
}
