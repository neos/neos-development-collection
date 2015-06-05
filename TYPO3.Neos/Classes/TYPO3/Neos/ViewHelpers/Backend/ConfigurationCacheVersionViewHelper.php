<?php
namespace TYPO3\Neos\ViewHelpers\Backend;

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
use TYPO3\Flow\Cache\Frontend\StringFrontend;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper for rendering the current version identifier for the
 * configuration cache.
 */
class ConfigurationCacheVersionViewHelper extends AbstractViewHelper {

	/**
	 * @var StringFrontend
	 */
	protected $configurationCache;

	/**
	 * @return string The current cache version identifier
	 */
	public function render() {
		$version = $this->configurationCache->get('ConfigurationVersion');
		if ($version === FALSE) {
			$version = time();
			$this->configurationCache->set('ConfigurationVersion', (string)$version);
		}
		return $version;
	}

}