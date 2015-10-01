<?php
namespace TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Package\Package as BasePackage;

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
            $dispatcher->connect('TYPO3\Flow\Core\Booting\Sequence', 'afterInvokeStep', function ($step) use ($bootstrap, $dispatcher) {
                if ($step->getIdentifier() === 'typo3.flow:systemfilemonitor') {
                    $typoScriptFileMonitor = \TYPO3\Flow\Monitor\FileMonitor::createFileMonitorAtBoot('TypoScript_Files', $bootstrap);
                    $packageManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface');
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
                    $cacheManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Cache\CacheManager');
                    $listener = new \TYPO3\TypoScript\Core\Cache\FileMonitorListener($cacheManager);
                    $dispatcher->connect('TYPO3\Flow\Monitor\FileMonitor', 'filesHaveChanged', $listener, 'flushContentCacheOnFileChanges');
                }
            });
        }
    }
}
