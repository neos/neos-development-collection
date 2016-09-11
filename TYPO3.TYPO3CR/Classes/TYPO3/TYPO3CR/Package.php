<?php
namespace TYPO3\TYPO3CR;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Core\Booting\Sequence;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Monitor\FileMonitor;
use TYPO3\Flow\Package\Package as BasePackage;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Persistence\Doctrine\PersistenceManager;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\Context;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

/**
 * The TYPO3CR Package
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
        $dispatcher->connect(PersistenceManager::class, 'allObjectsPersisted', NodeDataRepository::class, 'flushNodeRegistry');
        $dispatcher->connect(NodeDataRepository::class, 'repositoryObjectsPersisted', NodeDataRepository::class, 'flushNodeRegistry');
        $dispatcher->connect(Node::class, 'nodePathChanged', function () use ($bootstrap) {
            $contextFactory = $bootstrap->getObjectManager()->get(ContextFactoryInterface::class);
            /** @var Context $contextInstance */
            foreach ($contextFactory->getInstances() as $contextInstance) {
                $contextInstance->getFirstLevelNodeCache()->flush();
            }
        });

        $dispatcher->connect(ConfigurationManager::class, 'configurationManagerReady', function (ConfigurationManager $configurationManager) {
            $configurationManager->registerConfigurationType('NodeTypes', ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_DEFAULT, true);
        });

        $context = $bootstrap->getContext();
        if (!$context->isProduction()) {
            $dispatcher->connect(Sequence::class, 'afterInvokeStep', function ($step) use ($bootstrap) {
                if ($step->getIdentifier() === 'typo3.flow:systemfilemonitor') {
                    $nodeTypeConfigurationFileMonitor = FileMonitor::createFileMonitorAtBoot('TYPO3CR_NodeTypesConfiguration', $bootstrap);
                    $packageManager = $bootstrap->getEarlyInstance(PackageManagerInterface::class);
                    foreach ($packageManager->getActivePackages() as $packageKey => $package) {
                        if ($packageManager->isPackageFrozen($packageKey)) {
                            continue;
                        }
                        if (file_exists($package->getConfigurationPath())) {
                            $nodeTypeConfigurationFileMonitor->monitorDirectory($package->getConfigurationPath(), 'NodeTypes(\..+)\.yaml');
                        }
                    }

                    $nodeTypeConfigurationFileMonitor->monitorDirectory(FLOW_PATH_CONFIGURATION, 'NodeTypes(\..+)\.yaml');

                    $nodeTypeConfigurationFileMonitor->detectChanges();
                    $nodeTypeConfigurationFileMonitor->shutdownObject();
                }
            });
        }
    }
}
