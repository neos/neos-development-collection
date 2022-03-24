<?php
namespace Neos\Fusion;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Core\Booting\Sequence;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Monitor\FileMonitor;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Package\PackageManager;
use Neos\Fusion\Core\Cache\FileMonitorListener;
use Neos\Fusion\Core\Cache\ParserCache;

/**
 * The Neos Fusion Package
 */
class Package extends BasePackage
{
    /**
     * Invokes custom PHP code directly after the package manager has been initialized.
     *
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        $context = $bootstrap->getContext();
        if (!$context->isProduction()) {
            $dispatcher->connect(Sequence::class, 'afterInvokeStep', function ($step) use ($bootstrap, $dispatcher) {
                if ($step->getIdentifier() === 'neos.flow:systemfilemonitor') {
                    $fusionFileMonitor = FileMonitor::createFileMonitorAtBoot('Fusion_Files', $bootstrap);
                    /** @var PackageManager $packageManager */
                    $packageManager = $bootstrap->getEarlyInstance(PackageManager::class);
                    foreach ($packageManager->getFlowPackages() as $packageKey => $package) {
                        if ($packageManager->isPackageFrozen($packageKey)) {
                            continue;
                        }

                        $fusionPaths = [
                            $package->getResourcesPath() . 'Private/Fusion',
                            $package->getPackagePath() . 'NodeTypes'
                        ];
                        foreach ($fusionPaths as $fusionPath) {
                            if (is_dir($fusionPath)) {
                                $fusionFileMonitor->monitorDirectory($fusionPath);
                            }
                        }
                    }

                    $fusionFileMonitor->detectChanges();
                    $fusionFileMonitor->shutdownObject();
                }

                if ($step->getIdentifier() === 'neos.flow:cachemanagement') {
                    $cacheManager = $bootstrap->getEarlyInstance(CacheManager::class);
                    $listener = new FileMonitorListener($cacheManager);
                    $dispatcher->connect(FileMonitor::class, 'filesHaveChanged', $listener, 'flushContentCacheOnFileChanges');
                    // Use a closure to invoke the FusionParserCache, so the object is not instantiated during compiletime and has working DI
                    $flushParsePartialsCache = function ($identifier, $changedFilesAndStatus) use ($bootstrap) {
                        if ($identifier !== 'Fusion_Files') {
                            return;
                        }
                        $objectManager = $bootstrap->getObjectManager();
                        if ($objectManager->isRegistered(ParserCache::class) === false) {
                            // if we make a total `rm -rf Data/Temporary` all monitored `*.fusion` files will be seen as newly created.
                            // this triggers pretty early this `filesHaveChanged` and we still have the CompileTimeObjectManager
                            // we would get an exception like:
                            // Cannot build object "FusionParserCache" because it is unknown to the compile time Object Manager.
                            // so instead we will just make sure the cache is totally flushed.
                            $cacheManager = $bootstrap->getEarlyInstance(CacheManager::class);
                            $fusionParsePartialsCache = $cacheManager->getCache('Neos_Fusion_ParsePartials');
                            $fusionParsePartialsCache->flush();
                            return;
                        }
                        $fusionParserCache = $objectManager->get(ParserCache::class);
                        $fusionParserCache->flushFileAstCacheOnFileChanges($changedFilesAndStatus);
                    };
                    $dispatcher->connect(FileMonitor::class, 'filesHaveChanged', $flushParsePartialsCache);
                }
            });
        }
    }
}
