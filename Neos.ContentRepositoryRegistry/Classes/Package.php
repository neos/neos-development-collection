<?php

namespace Neos\ContentRepositoryRegistry;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepositoryRegistry\Configuration\NodeTypesLoader;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\Booting\Sequence;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Monitor\FileMonitor;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Package\PackageManager;
use Neos\Utility\Files;

/**
 * The ContentRepository Package
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

        $dispatcher->connect(ConfigurationManager::class, 'configurationManagerReady', function (ConfigurationManager $configurationManager) use ($bootstrap) {
            $configurationManager->registerConfigurationType('NodeTypes', new NodeTypesLoader(new YamlSource(), FLOW_PATH_CONFIGURATION, $bootstrap));
        });

        if ($bootstrap->getContext()->isProduction()) {
            return;
        }
        $dispatcher->connect(Sequence::class, 'afterInvokeStep', function ($step) use ($bootstrap) {
            if ($step->getIdentifier() === 'neos.flow:systemfilemonitor') {
                $nodeTypeConfigurationFileMonitor = FileMonitor::createFileMonitorAtBoot('ContentRepository_NodeTypesConfiguration', $bootstrap);
                /** @var PackageManager $packageManager */
                $packageManager = $bootstrap->getEarlyInstance(PackageManager::class);
                foreach ($packageManager->getFlowPackages() as $packageKey => $package) {
                    if ($packageManager->isPackageFrozen($packageKey)) {
                        continue;
                    }
                    if (file_exists($package->getConfigurationPath())) {
                        $nodeTypeConfigurationFileMonitor->monitorDirectory($package->getConfigurationPath(), 'NodeTypes(\..+)\.yaml');
                    }

                    $nodeTypesConfigurationDirectory = Files::concatenatePaths([$package->getPackagePath(), 'NodeTypes']);
                    if (\is_dir($nodeTypesConfigurationDirectory)) {
                        $nodeTypeConfigurationFileMonitor->monitorDirectory($nodeTypesConfigurationDirectory, '(.+)\.yaml');
                    }
                }

                $nodeTypeConfigurationFileMonitor->monitorDirectory(FLOW_PATH_CONFIGURATION, 'NodeTypes(\..+)\.yaml');

                $nodeTypeConfigurationFileMonitor->detectChanges();
                $nodeTypeConfigurationFileMonitor->shutdownObject();
            }
        });
        $dispatcher->connect(FileMonitor::class, 'filesHaveChanged', static function (string $fileMonitorIdentifier) use ($bootstrap) {
            if ($fileMonitorIdentifier === 'ContentRepository_NodeTypesConfiguration') {
                $bootstrap->getObjectManager()->get(ConfigurationManager::class)->refreshConfiguration();
            }
        });
    }
}
