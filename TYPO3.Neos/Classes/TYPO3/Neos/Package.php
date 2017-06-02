<?php
namespace TYPO3\Neos;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Monitor\FileMonitor;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;
use TYPO3\Flow\Package\Package as BasePackage;
use TYPO3\Flow\Persistence\Doctrine\PersistenceManager;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\Neos\Domain\Service\SiteImportService;
use TYPO3\Neos\Domain\Service\SiteService;
use TYPO3\Neos\EventLog\Integrations\TYPO3CRIntegrationService;
use TYPO3\Neos\Routing\Cache\RouteCacheFlusher;
use TYPO3\Neos\Service\PublishingService;
use TYPO3\Neos\Utility\NodeUriPathSegmentGenerator;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\Context;
use TYPO3\TypoScript\Core\Cache\ContentCache;

/**
 * The Neos Package
 */
class Package extends BasePackage
{
    /**
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        $flushConfigurationCache = function () use ($bootstrap) {
            $cacheManager = $bootstrap->getEarlyInstance(CacheManager::class);
            $cacheManager->getCache('TYPO3_Neos_Configuration_Version')->flush();
        };

        $flushXliffServiceCache = function () use ($bootstrap) {
            $cacheManager = $bootstrap->getEarlyInstance(CacheManager::class);
            $cacheManager->getCache('TYPO3_Neos_XliffToJsonTranslations')->flush();
        };

        $dispatcher->connect(FileMonitor::class, 'filesHaveChanged', function ($fileMonitorIdentifier, array $changedFiles) use ($flushConfigurationCache, $flushXliffServiceCache) {
            switch ($fileMonitorIdentifier) {
                case 'TYPO3CR_NodeTypesConfiguration':
                case 'Flow_ConfigurationFiles':
                    $flushConfigurationCache();
                    break;
                case 'Flow_TranslationFiles':
                    $flushConfigurationCache();
                    $flushXliffServiceCache();
            }
        });

        $dispatcher->connect(Site::class, 'siteChanged', $flushConfigurationCache);
        $dispatcher->connect(Site::class, 'siteChanged', 'TYPO3\Flow\Mvc\Routing\RouterCachingService', 'flushCaches');

        $dispatcher->connect(Node::class, 'nodeUpdated', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');
        $dispatcher->connect(Node::class, 'nodeAdded', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');
        $dispatcher->connect(Node::class, 'nodeRemoved', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');
        $dispatcher->connect(Node::class, 'beforeNodeMove', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');

        $dispatcher->connect('TYPO3\Media\Domain\Service\AssetService', 'assetResourceReplaced', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerAssetResourceChange');

        $dispatcher->connect(Node::class, 'nodeAdded', NodeUriPathSegmentGenerator::class, '::setUniqueUriPathSegment');
        $dispatcher->connect(Node::class, 'nodePropertyChanged', Service\ImageVariantGarbageCollector::class, 'removeUnusedImageVariant');
        $dispatcher->connect(Node::class, 'nodePropertyChanged', function (NodeInterface $node, $propertyName) use ($bootstrap) {
            if ($propertyName === 'uriPathSegment') {
                NodeUriPathSegmentGenerator::setUniqueUriPathSegment($node);
                $bootstrap->getObjectManager()->get(RouteCacheFlusher::class)->registerNodeChange($node);
            }
        });
        $dispatcher->connect(Node::class, 'nodePathChanged', function (NodeInterface $node, $oldPath, $newPath, $recursion) {
            if (!$recursion) {
                NodeUriPathSegmentGenerator::setUniqueUriPathSegment($node);
            }
        });

        $dispatcher->connect(PublishingService::class, 'nodePublished', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');
        $dispatcher->connect(PublishingService::class, 'nodeDiscarded', 'TYPO3\Neos\TypoScript\Cache\ContentCacheFlusher', 'registerNodeChange');

        $dispatcher->connect(Node::class, 'nodePathChanged', 'TYPO3\Neos\Routing\Cache\RouteCacheFlusher', 'registerNodeChange');
        $dispatcher->connect(Node::class, 'nodeRemoved', 'TYPO3\Neos\Routing\Cache\RouteCacheFlusher', 'registerNodeChange');
        $dispatcher->connect('TYPO3\Neos\Service\PublishingService', 'nodeDiscarded', 'TYPO3\Neos\Routing\Cache\RouteCacheFlusher', 'registerNodeChange');
        $dispatcher->connect(PublishingService::class, 'nodePublished', 'TYPO3\Neos\Routing\Cache\RouteCacheFlusher', 'registerNodeChange');
        $dispatcher->connect(PublishingService::class, 'nodePublished', function ($node, $targetWorkspace) use ($bootstrap) {
            $cacheManager = $bootstrap->getObjectManager()->get(CacheManager::class);
            if ($cacheManager->hasCache('Flow_Persistence_Doctrine')) {
                $cacheManager->getCache('Flow_Persistence_Doctrine')->flush();
            }
        });
        $dispatcher->connect(PersistenceManager::class, 'allObjectsPersisted', RouteCacheFlusher::class, 'commit');

        $dispatcher->connect(SiteService::class, 'sitePruned', ContentCache::class, 'flush');
        $dispatcher->connect(SiteService::class, 'sitePruned', RouterCachingService::class, 'flushCaches');

        $dispatcher->connect(SiteImportService::class, 'siteImported', ContentCache::class, 'flush');
        $dispatcher->connect(SiteImportService::class, 'siteImported', RouterCachingService::class, 'flushCaches');

        // Eventlog
        $dispatcher->connect(Node::class, 'beforeNodeCreate', TYPO3CRIntegrationService::class, 'beforeNodeCreate');
        $dispatcher->connect(Node::class, 'afterNodeCreate', TYPO3CRIntegrationService::class, 'afterNodeCreate');

        $dispatcher->connect(Node::class, 'nodeUpdated', TYPO3CRIntegrationService::class, 'nodeUpdated');
        $dispatcher->connect(Node::class, 'nodeRemoved', TYPO3CRIntegrationService::class, 'nodeRemoved');

        $dispatcher->connect(Node::class, 'beforeNodePropertyChange', TYPO3CRIntegrationService::class, 'beforeNodePropertyChange');
        $dispatcher->connect(Node::class, 'nodePropertyChanged', TYPO3CRIntegrationService::class, 'nodePropertyChanged');

        $dispatcher->connect(Node::class, 'beforeNodeCopy', TYPO3CRIntegrationService::class, 'beforeNodeCopy');
        $dispatcher->connect(Node::class, 'afterNodeCopy', TYPO3CRIntegrationService::class, 'afterNodeCopy');

        $dispatcher->connect(Node::class, 'beforeNodeMove', TYPO3CRIntegrationService::class, 'beforeNodeMove');
        $dispatcher->connect(Node::class, 'afterNodeMove', TYPO3CRIntegrationService::class, 'afterNodeMove');

        $dispatcher->connect(Context::class, 'beforeAdoptNode', TYPO3CRIntegrationService::class, 'beforeAdoptNode');
        $dispatcher->connect(Context::class, 'afterAdoptNode', TYPO3CRIntegrationService::class, 'afterAdoptNode');

        $dispatcher->connect(Workspace::class, 'beforeNodePublishing', TYPO3CRIntegrationService::class, 'beforeNodePublishing');
        $dispatcher->connect(Workspace::class, 'afterNodePublishing', TYPO3CRIntegrationService::class, 'afterNodePublishing');
        $dispatcher->connect(Workspace::class, 'baseWorkspaceChanged', 'TYPO3\Neos\Routing\Cache\RouteCacheFlusher', 'registerBaseWorkspaceChange');

        $dispatcher->connect(PersistenceManager::class, 'allObjectsPersisted', TYPO3CRIntegrationService::class, 'updateEventsAfterPublish');
        $dispatcher->connect(NodeDataRepository::class, 'repositoryObjectsPersisted', TYPO3CRIntegrationService::class, 'updateEventsAfterPublish');
    }
}
