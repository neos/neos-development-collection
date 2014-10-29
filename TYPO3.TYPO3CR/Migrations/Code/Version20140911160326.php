<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Adjust to renamed NodeLabelGeneratorInterface.
 * You should refactor your custom NodeLabelGenerators to implement the
 * (new) NodeLabelGeneratorInterface.
 */
class Version20140911160326 extends AbstractMigration {

	/**
	 * @return void
	 */
	public function up() {
		$this->searchAndReplace('NodeLabelGeneratorInterface', 'NodeDataLabelGeneratorInterface', array('php'));
		$this->searchAndReplace('DefaultNodeLabelGenerator', 'FallbackNodeDataLabelGenerator', array('php'));

		$this->processConfiguration(
			'NodeTypes',
			function (&$configuration) {
				foreach ($configuration as &$nodeType) {
					if (isset($nodeType['nodeLabelGenerator'])) {
						$nodeType['label'] = array (
							'generatorClass' => $nodeType['nodeLabelGenerator']
						);
						unset($nodeType['nodeLabelGenerator']);
					}
				}
			},
			TRUE
		);
	}

}
