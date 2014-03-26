<?php
namespace TYPO3\Neos;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Package\Package as BasePackage;

/**
 * The TYPO3 Neos Package
 */
class Package extends BasePackage {

	/**
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
		$dispatcher = $bootstrap->getSignalSlotDispatcher();

		$flushConfigurationCache = function () use ($bootstrap) {
			$cacheManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Cache\CacheManager');
			$cacheManager->getCache('TYPO3_Neos_Configuration_Version')->flush();
		};

		$dispatcher->connect('TYPO3\Flow\Monitor\FileMonitor', 'filesHaveChanged', $flushConfigurationCache);

		$dispatcher->connect('TYPO3\Neos\Domain\Model\Site', 'siteChanged', $flushConfigurationCache);
		$dispatcher->connect('TYPO3\Neos\Domain\Model\Site', 'siteChanged', 'TYPO3\Flow\Mvc\Routing\RouterCachingService', 'flushCaches');

		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodeUpdated', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodeAdded', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodeRemoved', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');
		$dispatcher->connect('TYPO3\Neos\Service\PublishingService', 'nodePublished', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');
		$dispatcher->connect('TYPO3\Neos\Service\PublishingService', 'nodeDiscarded', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');
	}

}
