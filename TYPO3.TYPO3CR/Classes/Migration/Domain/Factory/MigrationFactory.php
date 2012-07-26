<?php
namespace TYPO3\TYPO3CR\Migration\Domain\Factory;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Migration factory.
 *
 */
class MigrationFactory {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Migration\Configuration\ConfigurationInterface
	 */
	protected $migrationConfiguration;

	/**
	 * @param string $version
	 * @return \TYPO3\TYPO3CR\Migration\Domain\Model\Migration
	 */
	public function getMigrationForVersion($version) {
		$migrationConfiguration = $this->migrationConfiguration->getMigrationVersion($version);
		$migration = new \TYPO3\TYPO3CR\Migration\Domain\Model\Migration($version, $migrationConfiguration);
		return $migration;
	}

	/**
	 * Return array of all available migrations with the current configuration type
	 *
	 * @return array
	 */
	public function getAvailableMigrationsForCurrentConfigurationType() {
		return $this->migrationConfiguration->getAvailableVersions();
	}
}
?>