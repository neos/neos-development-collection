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

use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Package\Package as BasePackage;
use TYPO3\Flow\SignalSlot\Dispatcher;
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

		// Eventlog
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'beforeNodeCreate', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'beforeNodeCreate');
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'afterNodeCreate', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'afterNodeCreate');

		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodeUpdated', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'nodeUpdated');
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodeRemoved', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'nodeRemoved');

		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'beforeNodePropertyChange', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'beforeNodePropertyChange');
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodePropertyChanged', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'nodePropertyChanged');

		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'beforeNodeCopy', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'beforeNodeCopy');
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'afterNodeCopy', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'afterNodeCopy');

		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'beforeNodeMove', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'beforeNodeMove');
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'afterNodeMove', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'afterNodeMove');

		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Service\Context', 'beforeAdoptNode', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'beforeAdoptNode');
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Service\Context', 'afterAdoptNode', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'afterAdoptNode');

		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Workspace', 'beforeNodePublishing', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'beforeNodePublishing');
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Workspace', 'afterNodePublishing', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'afterNodePublishing');
		$dispatcher->connect('TYPO3\Neos\Service\PublishingService', 'nodeDiscarded', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'nodeDiscarded');

		$dispatcher->connect('TYPO3\Flow\Persistence\Doctrine\PersistenceManager', 'allObjectsPersisted', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'updateEventsAfterPublish');
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', 'repositoryObjectsPersisted', 'TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService', 'updateEventsAfterPublish');
	}
}
