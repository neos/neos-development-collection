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
 * Rename node property type 'date' to DateTime
 */
class Version20141218134700 extends AbstractMigration {

	/**
	 * @return void
	 */
	public function up() {
		$this->processConfiguration(
			'NodeTypes',
			function (&$configuration) {
				foreach ($configuration as $nodeTypeName => $nodeTypeConfiguration) {
					if (!isset($nodeTypeConfiguration['properties'])) {
						continue;
					}
					foreach ($nodeTypeConfiguration['properties'] as $propertyName => $propertyConfiguration) {
						if (isset($propertyConfiguration['type']) && $propertyConfiguration['type'] === 'date') {
							$configuration[$nodeTypeName]['properties'][$propertyName]['type'] = 'DateTime';
						}
					}
				}
			},
			TRUE
		);
	}
}
