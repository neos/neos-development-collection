<?php
namespace TYPO3\TYPO3CR\Migration\Configuration;

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
 * Abstract Migration Configuration as a base for different configuration sources.
 */
abstract class Configuration implements \TYPO3\TYPO3CR\Migration\Configuration\ConfigurationInterface {

	/**
	 * @var array
	 */
	protected $availableVersions = NULL;

	/**
	 * @var array
	 */
	protected $loadedVersions = array();

	/**
	 * Returns an array with all available versions.
	 *
	 * @return array
	 */
	public function getAvailableVersions() {
		if ($this->availableVersions === NULL) {
			$this->registerAvailableVersions();
		}
		return $this->availableVersions;
	}

	/**
	 * If the given version is available, TRUE is returned.
	 *
	 * @param string $version
	 * @return boolean
	 */
	public function isVersionAvailable($version) {
		if ($this->availableVersions === NULL) {
			$this->registerAvailableVersions();
		}
		return isset($this->availableVersions[$version]);
	}

	/**
	 * Returns the configuration of the given version, if available.
	 *
	 * @param string $version
	 * @return array
	 * @throws \TYPO3\TYPO3CR\Migration\Exception\MigrationException
	 */
	public function getMigrationVersion($version) {
		if ($this->isVersionAvailable($version)) {
			if ($this->isVersionLoaded($version)) {
				$configuration = $this->loadedVersions[$version];
			} else {
				$configuration = $this->loadConfiguration($version);
				$this->loadedVersions[$version] = $configuration;
			}
			return $configuration;
		}
		throw new \TYPO3\TYPO3CR\Migration\Exception\MigrationException('Specified version is not available.', 1345821746);
	}

	/**
	 * Check if the given version has been loaded already.
	 *
	 * @param string $version
	 * @return boolean
	 */
	protected function isVersionLoaded($version) {
		return array_key_exists($version, $this->loadedVersions);
	}

	/**
	 * Loads a specific version into an array.
	 *
	 * @param string $version
	 * @return array
	 */
	abstract protected function loadConfiguration($version);

	/**
	 * Loads a list of available versions into an array.
	 *
	 * @return array
	 */
	abstract protected function registerAvailableVersions();

}

?>