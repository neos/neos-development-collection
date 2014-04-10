<?php
namespace TYPO3\TypoScript\Core\Cache;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TypoScript".      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Listener to clear TypoScript caches if important files have changed
 *
 * It's used in the Package bootstrap as an early instance, so no full dependency injection is available.
 *
 * @Flow\Proxy(false)
 */
class FileMonitorListener {

	/**
	 * @var \TYPO3\Flow\Cache\CacheManager
	 */
	protected $flowCacheManager;

	/**
	 * @param \TYPO3\Flow\Cache\CacheManager $flowCacheManager
	 */
	public function __construct(\TYPO3\Flow\Cache\CacheManager $flowCacheManager) {
		$this->flowCacheManager = $flowCacheManager;
	}

	/**
	 * @param $fileMonitorIdentifier
	 * @param array $changedFiles
	 * @return void
	 */
	public function flushContentCacheOnFileChanges($fileMonitorIdentifier, array $changedFiles) {
		$fileMonitorsThatTriggerContentCacheFlush = array(
			'TYPO3CR_NodeTypesConfiguration',
			'TypoScript_Files',
			'Fluid_TemplateFiles',
			'Flow_ClassFiles',
			'Flow_ConfigurationFiles',
			'Flow_TranslationFiles'
		);

		if (in_array($fileMonitorIdentifier, $fileMonitorsThatTriggerContentCacheFlush)) {
			$this->flowCacheManager->getCache('TYPO3_TypoScript_Content')->flush();
		}
	}
}