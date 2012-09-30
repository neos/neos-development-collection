<?php
namespace TYPO3\TYPO3CR\Migration\Configuration;

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
use TYPO3\Flow\Utility\Files as Files;

/**
 * Migration Configuration using YAML files.
 */
class YamlConfiguration extends \TYPO3\TYPO3CR\Migration\Configuration\Configuration {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Configuration\Source\YamlSource
	 */
	protected $yamlSourceImporter;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * Loads a list of available versions into an array.
	 *
	 * @return array
	 */
	protected function registerAvailableVersions() {
		$this->availableVersions = array();
		foreach ($this->packageManager->getActivePackages() as $package) {
			$possibleMigrationsPath = \TYPO3\Flow\Utility\Files::concatenatePaths(array(
				$package->getPackagePath(),
				'Migrations/TYPO3CR'
			));
			if (!is_dir($possibleMigrationsPath)) {
				continue;
			}
			$directoryIterator = new \DirectoryIterator($possibleMigrationsPath);
			foreach ($directoryIterator as $fileInfo) {
				$filename = $fileInfo->getFilename();
				if ($fileInfo->isFile() && $filename[0] !== '.' && (substr($filename, -5) === '.yaml')) {
					$versionFile = Files::getUnixStylePath($fileInfo->getPathname());
					$versionNumber = substr(substr($filename, 7), 0, -5);
					if (array_key_exists($versionNumber, $this->availableVersions)) {
						throw new \TYPO3\TYPO3CR\Migration\Exception\MigrationException('The migration version ' . $versionNumber . ' exists twice, that is not supported.', 1345823182);
					}
					$this->availableVersions[$versionNumber] = array (
						'filePathAndName' => $versionFile,
						'package' => $package,
						'formattedVersionNumber' =>
								// DD-MM-YYYY HH:MM:SS
							$versionNumber[6] . $versionNumber[7] . '-' .
							$versionNumber[4] . $versionNumber[5] . '-' .
							$versionNumber[0] . $versionNumber[1] . $versionNumber[2] . $versionNumber[3] . ' ' .
							$versionNumber[8] . $versionNumber[9] . ':' . $versionNumber[10] . $versionNumber[11] . ':' . $versionNumber[12] . $versionNumber[13]
					);
				}
			}
		}
		ksort($this->availableVersions);
	}

	/**
	 * Loads a specific version into an array.
	 *
	 * @param string $version
	 * @return array
	 * @throws \TYPO3\TYPO3CR\Migration\Exception\MigrationException
	 */
	protected function loadConfiguration($version) {
		if (!$this->isVersionAvailable($version)) {
			throw new \TYPO3\TYPO3CR\Migration\Exception\MigrationException('The requested YamlConfiguration was not available.', 1345822283);
		}

		$configuration = $this->yamlSourceImporter->load(substr($this->availableVersions[$version]['filePathAndName'], 0, -5));
		return $configuration;
	}
}
?>