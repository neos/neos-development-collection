<?php
namespace TYPO3\TypoScript;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Core\Booting\Sequence;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Monitor\FileMonitor;
use TYPO3\Flow\Package\Package as BasePackage;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\TypoScript\Core\Cache\FileMonitorListener;

/**
 * The TYPO3 TypoScript Package
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
                if ($step->getIdentifier() === 'typo3.flow:systemfilemonitor') {
                    $typoScriptFileMonitor = FileMonitor::createFileMonitorAtBoot('TypoScript_Files', $bootstrap);
                    $packageManager = $bootstrap->getEarlyInstance(PackageManagerInterface::class);
                    foreach ($packageManager->getActivePackages() as $packageKey => $package) {
                        if ($packageManager->isPackageFrozen($packageKey)) {
                            continue;
                        }
                        $typoScriptPaths = array(
                            $package->getResourcesPath() . 'Private/TypoScript',
                            $package->getResourcesPath() . 'Private/TypoScripts',
                        );
                        foreach ($typoScriptPaths as $typoScriptPath) {
                            if (is_dir($typoScriptPath)) {
                                $typoScriptFileMonitor->monitorDirectory($typoScriptPath);
                            }
                        }
                    }

                    $typoScriptFileMonitor->detectChanges();
                    $typoScriptFileMonitor->shutdownObject();
                }

                if ($step->getIdentifier() === 'typo3.flow:cachemanagement') {
                    $cacheManager = $bootstrap->getEarlyInstance(CacheManager::class);
                    $listener = new FileMonitorListener($cacheManager);
                    $dispatcher->connect(FileMonitor::class, 'filesHaveChanged', $listener, 'flushContentCacheOnFileChanges');
                }
            });
        }
    }
}
