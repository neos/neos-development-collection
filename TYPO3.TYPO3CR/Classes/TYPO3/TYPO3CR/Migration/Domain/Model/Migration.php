<?php
namespace TYPO3\TYPO3CR\Migration\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Migration.
 *
 */
class Migration {

	/**
	 * Version that was migrated to.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * @var MigrationConfiguration
	 */
	protected $upConfiguration;

	/**
	 * @var MigrationConfiguration
	 */
	protected $downConfiguration;

	/**
	 * @param string $version
	 * @param array $configuration
	 */
	public function __construct($version, array $configuration) {
		$this->version = $version;
		$this->upConfiguration = new MigrationConfiguration($configuration[MigrationStatus::DIRECTION_UP]);
		$this->downConfiguration = new MigrationConfiguration($configuration[MigrationStatus::DIRECTION_DOWN]);
	}

	/**
	 * @return string
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * @return \TYPO3\TYPO3CR\Migration\Domain\Model\MigrationConfiguration
	 */
	public function getDownConfiguration() {
		return $this->downConfiguration;
	}

	/**
	 * @return \TYPO3\TYPO3CR\Migration\Domain\Model\MigrationConfiguration
	 */
	public function getUpConfiguration() {
		return $this->upConfiguration;
	}
}
?>