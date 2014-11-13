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
 * Adjust to removed TYPO3.TYPO3CR:Folder node type by replacing it
 * with unstructured. In a Neos context, you probably want to replace
 * it with TYPO3.Neos:Document instead!
 */
class Version20130523180140 extends AbstractMigration {

	/**
	 * NOTE: This method is overridden for historical reasons. Previously code migrations were expected to consist of the
	 * string "Version" and a 12-character timestamp suffix. The suffix has been changed to a 14-character timestamp.
	 * For new migrations the classname pattern should be "Version<YYYYMMDDhhmmss>" (14-character timestamp) and this method should *not* be implemented
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return 'TYPO3.TYPO3CR-130523180140';
	}

	/**
	 * @return void
	 */
	public function up() {
		$this->searchAndReplace('TYPO3.TYPO3CR:Folder', 'unstructured', array('php', 'ts2'));

		$this->processConfiguration(
			'NodeTypes',
			function (&$configuration) {
				foreach ($configuration as &$nodeType) {
					if (isset($nodeType['superTypes'])) {
						foreach ($nodeType['superTypes'] as &$superType) {
							$superType = str_replace('TYPO3.TYPO3CR:Folder', 'unstructured', $superType);
						}
					}
					if (isset($nodeType['childNodes'])) {
						foreach ($nodeType['childNodes'] as &$type) {
							$type = str_replace('TYPO3.TYPO3CR:Folder', 'unstructured', $type);
						}
					}
				}
			},
			TRUE
		);
	}

}
