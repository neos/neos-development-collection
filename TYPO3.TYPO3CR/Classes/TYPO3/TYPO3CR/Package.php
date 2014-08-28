<?php
namespace TYPO3\TYPO3CR;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Package\Package as BasePackage;

/**
 * The TYPO3CR Package
 */
class Package extends BasePackage {

	/**
	 * Invokes custom PHP code directly after the package manager has been initialized.
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
		$dispatcher = $bootstrap->getSignalSlotDispatcher();
		$dispatcher->connect('TYPO3\Flow\Persistence\Doctrine\PersistenceManager', 'allObjectsPersisted', 'TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', 'flushNodeRegistry');
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', 'repositoryObjectsPersisted', 'TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository', 'flushNodeRegistry');
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\NodeData', 'nodePathChanged', 'TYPO3\Flow\Mvc\Routing\RouterCachingService', 'flushCaches');
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\NodeData', 'nodePathChanged', 'TYPO3\TYPO3CR\Domain\Service\ContextFactory', 'flushFirstLevelNodeCaches');
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Workspace', 'beforeNodePublishing', 'TYPO3\TYPO3CR\Domain\Service\NodeService', 'cleanUpProperties');

		$dispatcher->connect('TYPO3\Flow\Configuration\ConfigurationManager', 'configurationManagerReady', function(ConfigurationManager $configurationManager) {
			$configurationManager->registerConfigurationType('NodeTypes', ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_DEFAULT, TRUE);
		});

		$context = $bootstrap->getContext();
		if (!$context->isProduction()) {
			$dispatcher->connect('TYPO3\Flow\Core\Booting\Sequence', 'afterInvokeStep', function($step) use ($bootstrap) {
				if  ($step->getIdentifier() === 'typo3.flow:systemfilemonitor') {
					$nodeTypeConfigurationFileMonitor = \TYPO3\Flow\Monitor\FileMonitor::createFileMonitorAtBoot('TYPO3CR_NodeTypesConfiguration', $bootstrap);
					$packageManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface');
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
