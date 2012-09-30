<?php
namespace TYPO3\TYPO3CR\Migration\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\Migration\Service\NodeMigration as NodeMigration;
use TYPO3\TYPO3CR\Migration\Domain\Model\MigrationStatus as MigrationStatus;
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
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

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
		$this->setContextOnNodeRepository($workspace);
		$migrationConfiguration = $direction === MigrationStatus::DIRECTION_UP ?
			$this->migrationFactory->getMigrationForVersion($version)->getUpConfiguration() :
			$this->migrationFactory->getMigrationForVersion($version)->getDownConfiguration();

		$this->outputCommentsAndWarnings($migrationConfiguration);
		if ($migrationConfiguration->hasWarnings() && $confirmation === FALSE) {
			$this->outputLine();
			$this->outputLine('Migration has warnings. You need to confirm execution by adding the "--confirmation TRUE" option to the command.');
			$this->quit(1);
		}

		$nodeMigrationService = new NodeMigration($workspace, $migrationConfiguration->getMigration());
		switch ($direction) {
			case MigrationStatus::DIRECTION_UP:
				$nodeMigrationService->migrateUp();
			break;
			case MigrationStatus::DIRECTION_DOWN:
				$nodeMigrationService->migrateDown();
			break;
			default:

		}
		$migrationStatus = new \TYPO3\TYPO3CR\Migration\Domain\Model\MigrationStatus($version, $workspace, $direction, new \DateTime());
		$this->migrationStatusRepository->add($migrationStatus);
		$this->outputLine();
		$this->outputLine('Successfully applied migration.');
	}

	/**
	 * Prints a list of available migration versions and the packages they come from
	 *
	 * @return void
	 * @see typo3.typo3cr.migration:node:migrationstatus
	 */
	public function listAvailableMigrationsCommand() {
		$availableMigrations = $this->migrationFactory->getAvailableMigrationsForCurrentConfigurationType();
		if (count($availableMigrations) > 0) {
			$this->outputLine('<b>Available migrations</b>');
			$this->outputLine();
			foreach ($availableMigrations as $version => $migration) {
				$this->outputLine($version . '   ' . $migration['formattedVersionNumber'] . '   ' . $migration['package']->getPackageKey());
			}
		} else {
			$this->outputLine('No migrations available.');
		}
	}

	/**
	 * List applied migrations
	 *
	 * @param string $workspace
	 * @return void
	 * @see typo3.typo3cr.migration:node:listavailablemigrations
	 */
	public function migrationStatusCommand($workspace = NULL) {
		$this->outputLine();
		if ($workspace !== NULL) {
			$appliedMigrations = $this->migrationStatusRepository->findByWorkspaceName($workspace);
		} else {
			$appliedMigrations = $this->migrationStatusRepository->findAll();
		}
		if (count($appliedMigrations) > 0) {
			$this->outputLine('<b>Applied migrations</b>');
			$this->outputLine();
			foreach ($appliedMigrations as $appliedMigration) {
				$this->outputLine(
					$appliedMigration->getVersion()
						. ' ' . str_pad($appliedMigration->getDirection(), 4, ' ')
						. ' applied on ' . $appliedMigration->getApplicationTimeStamp()->format('d-m-Y H:i:s')
						. ' to workspace "' . $appliedMigration->getWorkspaceName() . '"'
				);
			}
		} else {
			$this->outputLine('No migrations applied.');
		}
	}

	/**
	 * Helper to output comments and warnings for the given configuration.
	 *
	 * @param \TYPO3\TYPO3CR\Migration\Domain\Model\MigrationConfiguration $migrationConfiguration
	 * @return void
	 */
	protected function outputCommentsAndWarnings(\TYPO3\TYPO3CR\Migration\Domain\Model\MigrationConfiguration $migrationConfiguration) {
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
	 * @return void
	 */
	protected function setContextOnNodeRepository($workspaceName) {
		$context = new \TYPO3\TYPO3CR\Domain\Service\Context($workspaceName);
		$context->setInaccessibleContentShown(TRUE);
		$context->setInvisibleContentShown(TRUE);
		$context->setRemovedContentShown(TRUE);
		$this->nodeRepository->setContext($context);
	}
}
?>