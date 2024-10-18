<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos;

use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Monitor\FileMonitor;
use Neos\Flow\Mvc\Routing\RouterCachingService;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Fusion\Core\Cache\ContentCache;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Service\AssetService;
use Neos\Neos\AssetUsage\GlobalAssetUsageService;
use Neos\Neos\Controller\Backend\ContentController;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Service\SiteService;
use Neos\Neos\Fusion\Cache\AssetChangeHandlerForCacheFlushing;
use Neos\Neos\Routing\Cache\RouteCacheFlusher;

/**
 * The Neos Package
 */
class Package extends BasePackage
{
    /**
     * @var boolean
     */
    protected $protected = true;

    /**
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        $flushConfigurationCache = function () use ($bootstrap) {
            /** @var CacheManager $cacheManager */
            $cacheManager = $bootstrap->getEarlyInstance(CacheManager::class);
            $cacheManager->getCache('Neos_Neos_Configuration_Version')->flush();
        };

        $flushXliffServiceCache = function () use ($bootstrap) {
            /** @var CacheManager $cacheManager */
            $cacheManager = $bootstrap->getEarlyInstance(CacheManager::class);
            $cacheManager->getCache('Neos_Neos_XliffToJsonTranslations')->flush();
        };

        $dispatcher->connect(
            FileMonitor::class,
            'filesHaveChanged',
            function (
                $fileMonitorIdentifier,
                array $changedFiles,
            ) use (
                $flushConfigurationCache,
                $flushXliffServiceCache
            ) {
                switch ($fileMonitorIdentifier) {
                    case 'ContentRepository_NodeTypesConfiguration':
                    case 'Flow_ConfigurationFiles':
                        $flushConfigurationCache();
                        break;
                    case 'Flow_TranslationFiles':
                        $flushConfigurationCache();
                        $flushXliffServiceCache();
                }
            }
        );

        $dispatcher->connect(
            Site::class,
            'siteChanged',
            $flushConfigurationCache
        );
        $dispatcher->connect(
            Site::class,
            'siteChanged',
            RouterCachingService::class,
            'flushCaches'
        );

        $dispatcher->connect(
            AssetService::class,
            'assetUpdated',
            AssetChangeHandlerForCacheFlushing::class,
            'registerAssetChange',
            false
        );

        $dispatcher->connect(
            ContentController::class,
            'assetUploaded',
            SiteService::class,
            'assignUploadedAssetToSiteAssetCollection'
        );

        $dispatcher->connect(
            PersistenceManager::class,
            'allObjectsPersisted',
            RouteCacheFlusher::class,
            'commit'
        );

        $dispatcher->connect(
            SiteService::class,
            'sitePruned',
            ContentCache::class,
            'flush'
        );
        $dispatcher->connect(
            SiteService::class,
            'sitePruned',
            RouterCachingService::class,
            'flushCaches'
        );

        $dispatcher->connect(AssetService::class, 'assetRemoved', function (AssetInterface $asset) use ($bootstrap) {

            /** @var GlobalAssetUsageService $globalAssetUsageService */
            $globalAssetUsageService = $bootstrap->getObjectManager()->get(GlobalAssetUsageService::class);

            /** @var PersistenceManagerInterface $persistenceManager */
            $persistenceManager = $bootstrap->getObjectManager()->get(PersistenceManagerInterface::class);
            $assetIdentifier = $persistenceManager->getIdentifierByObject($asset);
            if (is_string($assetIdentifier)) {
                $globalAssetUsageService->removeAssetUsageByAssetId($assetIdentifier);
            }
        });
    }
}
