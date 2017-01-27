<?php
namespace Neos\Neos;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Monitor\FileMonitor;
use Neos\Flow\Mvc\Routing\RouterCachingService;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Media\Domain\Service\AssetService;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\Neos\Domain\Service\SiteService;
use Neos\Neos\EventLog\Integrations\ContentRepositoryIntegrationService;
use Neos\Neos\Routing\Cache\RouteCacheFlusher;
use Neos\Neos\Service\PublishingService;
use Neos\Neos\Fusion\Cache\ContentCacheFlusher;
use Neos\Neos\Utility\NodeUriPathSegmentGenerator;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\Fusion\Core\Cache\ContentCache;

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
            $cacheManager->getCache('Neos_Neos_Configuration_Version')->flush();
        };

        $flushXliffServiceCache = function () use ($bootstrap) {
            $cacheManager = $bootstrap->getEarlyInstance(CacheManager::class);
            $cacheManager->getCache('Neos_Neos_XliffToJsonTranslations')->flush();
        };

        $dispatcher->connect(FileMonitor::class, 'filesHaveChanged', function ($fileMonitorIdentifier, array $changedFiles) use ($flushConfigurationCache, $flushXliffServiceCache) {
            switch ($fileMonitorIdentifier) {
                case 'ContentRepository_NodeTypesConfiguration':
                case 'Flow_ConfigurationFiles':
                    $flushConfigurationCache();
                    break;
                case 'Flow_TranslationFiles':
                    $flushConfigurationCache();
                    $flushXliffServiceCache();
            }
        });

        $dispatcher->connect(Site::class, 'siteChanged', $flushConfigurationCache);
        $dispatcher->connect(Site::class, 'siteChanged', RouterCachingService::class, 'flushCaches');

        $dispatcher->connect(Node::class, 'nodeUpdated', ContentCacheFlusher::class, 'registerNodeChange');
        $dispatcher->connect(Node::class, 'nodeAdded', ContentCacheFlusher::class, 'registerNodeChange');
        $dispatcher->connect(Node::class, 'nodeRemoved', ContentCacheFlusher::class, 'registerNodeChange');
        $dispatcher->connect(Node::class, 'beforeNodeMove', ContentCacheFlusher::class, 'registerNodeChange');

        $dispatcher->connect(AssetService::class, 'assetResourceReplaced', ContentCacheFlusher::class, 'registerAssetResourceChange');

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

        $dispatcher->connect(PublishingService::class, 'nodePublished', ContentCacheFlusher::class, 'registerNodeChange');
        $dispatcher->connect(PublishingService::class, 'nodeDiscarded', ContentCacheFlusher::class, 'registerNodeChange');

        $dispatcher->connect(Node::class, 'nodePathChanged', RouteCacheFlusher::class, 'registerNodeChange');
        $dispatcher->connect(Node::class, 'nodeRemoved', RouteCacheFlusher::class, 'registerNodeChange');
        $dispatcher->connect(PublishingService::class, 'nodeDiscarded', RouteCacheFlusher::class, 'registerNodeChange');
        $dispatcher->connect(PublishingService::class, 'nodePublished', RouteCacheFlusher::class, 'registerNodeChange');
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
        $dispatcher->connect(Node::class, 'beforeNodeCreate', ContentRepositoryIntegrationService::class, 'beforeNodeCreate');
        $dispatcher->connect(Node::class, 'afterNodeCreate', ContentRepositoryIntegrationService::class, 'afterNodeCreate');

        $dispatcher->connect(Node::class, 'nodeUpdated', ContentRepositoryIntegrationService::class, 'nodeUpdated');
        $dispatcher->connect(Node::class, 'nodeRemoved', ContentRepositoryIntegrationService::class, 'nodeRemoved');

        $dispatcher->connect(Node::class, 'beforeNodePropertyChange', ContentRepositoryIntegrationService::class, 'beforeNodePropertyChange');
        $dispatcher->connect(Node::class, 'nodePropertyChanged', ContentRepositoryIntegrationService::class, 'nodePropertyChanged');

        $dispatcher->connect(Node::class, 'beforeNodeCopy', ContentRepositoryIntegrationService::class, 'beforeNodeCopy');
        $dispatcher->connect(Node::class, 'afterNodeCopy', ContentRepositoryIntegrationService::class, 'afterNodeCopy');

        $dispatcher->connect(Node::class, 'beforeNodeMove', ContentRepositoryIntegrationService::class, 'beforeNodeMove');
        $dispatcher->connect(Node::class, 'afterNodeMove', ContentRepositoryIntegrationService::class, 'afterNodeMove');

        $dispatcher->connect(Context::class, 'beforeAdoptNode', ContentRepositoryIntegrationService::class, 'beforeAdoptNode');
        $dispatcher->connect(Context::class, 'afterAdoptNode', ContentRepositoryIntegrationService::class, 'afterAdoptNode');

        $dispatcher->connect(Workspace::class, 'beforeNodePublishing', ContentRepositoryIntegrationService::class, 'beforeNodePublishing');
        $dispatcher->connect(Workspace::class, 'afterNodePublishing', ContentRepositoryIntegrationService::class, 'afterNodePublishing');

        $dispatcher->connect(PersistenceManager::class, 'allObjectsPersisted', ContentRepositoryIntegrationService::class, 'updateEventsAfterPublish');
        $dispatcher->connect(NodeDataRepository::class, 'repositoryObjectsPersisted', ContentRepositoryIntegrationService::class, 'updateEventsAfterPublish');
    }
}
