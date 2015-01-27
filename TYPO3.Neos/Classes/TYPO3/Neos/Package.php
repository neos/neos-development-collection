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

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Package\Package as BasePackage;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * The TYPO3 Neos Package
 */
class Package extends BasePackage {

	/**
	 * @param Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(Bootstrap $bootstrap) {
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

		$uriPathSegmentGenerator = function(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
			if ($node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
				if(!$node->hasProperty('uriPathSegment')) {
					$node->setProperty('uriPathSegment', $node->getName());
				}

				$q = new FlowQuery(array($node));
				$possibleUriPathSegment = $node->getProperty('uriPathSegment');
				while (count($q->siblings('[uriPathSegment=' . $possibleUriPathSegment . ']')->get())) {
					$possibleUriPathSegment = $node->getProperty('uriPathSegment') . '-' . uniqid();
				}
				$node->setProperty('uriPathSegment', $possibleUriPathSegment);
			}
		};
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodeUpdated', $uriPathSegmentGenerator);
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodeAdded', $uriPathSegmentGenerator);

		$dispatcher->connect('TYPO3\Neos\Service\PublishingService', 'nodePublished', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');
		$dispatcher->connect('TYPO3\Neos\Service\PublishingService', 'nodeDiscarded', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');

		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\NodeData', 'nodePathChanged', 'TYPO3\Neos\Routing\Cache\RouteCacheFlusher', 'registerNodePathChange');
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodeRemoved', 'TYPO3\Neos\Routing\Cache\RouteCacheFlusher', 'registerNodeChange');
		$dispatcher->connect('TYPO3\Neos\Service\PublishingService', 'nodePublished', 'TYPO3\Neos\Routing\Cache\RouteCacheFlusher', 'registerNodeChange');
		$dispatcher->connect('TYPO3\Neos\Service\PublishingService', 'nodeDiscarded', 'TYPO3\Neos\Routing\Cache\RouteCacheFlusher', 'registerNodeChange');
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodePropertyChanged', function(NodeInterface $node, $propertyName) use($bootstrap) {
			if ($propertyName === 'uriPathSegment') {
				$bootstrap->getObjectManager()->get('TYPO3\Neos\Routing\Cache\RouteCacheFlusher')->registerNodeChange($node);
			}
		});
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', 'repositoryObjectsPersisted', 'TYPO3\Neos\Routing\Cache\RouteCacheFlusher', 'commit');

		$dispatcher->connect('TYPO3\Neos\Domain\Service\SiteService', 'sitePruned', 'TYPO3\TypoScript\Core\Cache\ContentCache', 'flush');
		$dispatcher->connect('TYPO3\Neos\Domain\Service\SiteService', 'sitePruned', 'TYPO3\Flow\Mvc\Routing\RouterCachingService', 'flushCaches');

		$dispatcher->connect('TYPO3\Neos\Domain\Service\SiteImportService', 'siteImported', 'TYPO3\TypoScript\Core\Cache\ContentCache', 'flush');
		$dispatcher->connect('TYPO3\Neos\Domain\Service\SiteImportService', 'siteImported', 'TYPO3\Flow\Mvc\Routing\RouterCachingService', 'flushCaches');
	}

}
