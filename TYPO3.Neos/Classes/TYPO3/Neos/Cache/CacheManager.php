<?php
namespace TYPO3\Neos\Cache;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A cache manager for Neos caches
 *
 * @Flow\Scope("singleton")
 */
class CacheManager {

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\StringFrontend
	 */
	protected $configurationCache;

	/**
	 * Get the version of the configuration cache
	 *
	 * This value will be changed on every change of configuration files.
	 *
	 * @return string
	 */
	public function getConfigurationCacheVersion() {
		$version = $this->configurationCache->get('ConfigurationVersion');
		if ($version === FALSE) {
			$version = time();
			$this->configurationCache->set('ConfigurationVersion', (string)$version);
		}
		return $version;
	}

	/**
	 * @param \TYPO3\Flow\Cache\Frontend\StringFrontend $configurationCache
	 */
	public function setConfigurationCache($configurationCache) {
		$this->configurationCache = $configurationCache;
	}

}
?>