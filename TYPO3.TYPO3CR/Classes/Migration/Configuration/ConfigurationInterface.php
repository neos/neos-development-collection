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
 * Interface for Migration Configurations to allow different configuration sources.
 */
interface ConfigurationInterface {

	/**
	 * Returns all available versions.
	 *
	 * @return array
	 */
	public function getAvailableVersions();

	/**
	 * Is the given version available?
	 *
	 * @param string $version
	 * @return boolean
	 */
	public function isVersionAvailable($version);

	/**
	 * Returns the migration configuration with the given version.
	 *
	 * @param string $version
	 * @return array
	 */
	public function getMigrationVersion($version);
}

?>