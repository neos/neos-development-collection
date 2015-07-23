<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Rename setting TYPO3.Neos.modules.<moduleName>.resource to "privilegeTarget"
 */
class Version20141113115300 extends AbstractMigration {

	/**
	 * @return void
	 */
	public function up() {
		$this->processConfiguration(
			'Settings',
			function (&$configuration) {
				if (!isset($configuration['TYPO3']['Neos']['modules'])) {
					return;
				}
				foreach ($configuration['TYPO3']['Neos']['modules'] as &$moduleConfiguration) {
					$this->processModuleConfiguration($moduleConfiguration);
				}
			},
			TRUE
		);
	}

	/**
	 * @param array $moduleConfiguration
	 * @return void
	 */
	protected function processModuleConfiguration(array &$moduleConfiguration) {
		if (isset($moduleConfiguration['resource'])) {
			$moduleConfiguration['privilegeTarget'] = $moduleConfiguration['resource'];
			unset($moduleConfiguration['resource']);
		}
		if (isset($moduleConfiguration['submodules'])) {
			foreach ($moduleConfiguration['submodules'] as &$subModuleConfiguration) {
				$this->processModuleConfiguration($subModuleConfiguration);
			}
		}
	}
}
