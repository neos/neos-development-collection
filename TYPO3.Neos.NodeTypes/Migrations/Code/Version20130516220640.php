<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Neos.NodeTypes".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Utility\Files;

/**
 * Change node type and TS object names in Nodetypes.yaml, TypoScript files and PHP code
 */
class Version20130516220640 extends AbstractMigration {

	/**
	 * @return void
	 */
	public function up() {
		$this->searchAndReplace('TYPO3.TypoScript:Template', 'TYPO3.Neos:Template', array('php', 'ts2'));
		$this->searchAndReplace('TYPO3.Neos.NodeTypes:Page', 'TYPO3.Neos:Page', array('php', 'ts2'));
		$this->searchAndReplace('TYPO3.Neos.NodeTypes:Shortcut', 'TYPO3.Neos:Shortcut', array('php', 'ts2'));
		$this->searchAndReplace('TYPO3.Neos.NodeTypes:ContentObject', 'TYPO3.Neos:Content', array('php', 'ts2'));
		$this->searchAndReplace('TYPO3.Neos.NodeTypes:Section', 'TYPO3.Neos:ContentCollection', array('php', 'ts2'));
		$this->searchAndReplace('TYPO3.Neos.NodeTypes:AbstractNode', 'TYPO3.Neos:Node', array('php', 'ts2'));
		$this->searchAndReplace('TYPO3.Neos.NodeTypes:Plugin', 'TYPO3.Neos:Plugin', array('php', 'ts2'));
		$this->searchAndReplace('TYPO3.Neos.NodeTypes:Folder', 'TYPO3.Neos:Document', array('php', 'ts2'));
		$this->searchAndReplace('TYPO3.TYPO3CR:Folder', 'TYPO3.Neos:Document', array('php', 'ts2'));
		$this->searchAndReplace('Section', 'ContentCollection', array('ts2'));

		$this->processConfiguration(
			'NodeTypes',
			function (&$configuration) {
				foreach ($configuration as &$nodeType) {
					if (isset($nodeType['superTypes'])) {
						foreach ($nodeType['superTypes'] as &$superType) {
							$superType = str_replace(
								array(
									'TYPO3.Neos.NodeTypes:Page',
									'TYPO3.Neos.NodeTypes:Shortcut',
									'TYPO3.Neos.NodeTypes:ContentObject',
									'TYPO3.Neos.NodeTypes:Section',
									'TYPO3.Neos.NodeTypes:AbstractNode',
									'TYPO3.Neos.NodeTypes:Plugin',
									'TYPO3.Neos.NodeTypes:Folder'
								),
								array(
									'TYPO3.Neos:Page',
									'TYPO3.Neos:Shortcut',
									'TYPO3.Neos:Content',
									'TYPO3.Neos:ContentCollection',
									'TYPO3.Neos:Node',
									'TYPO3.Neos:Plugin',
									'TYPO3.Neos:Document'
								),
								$superType
							);
						}
					}
					if (isset($nodeType['childNodes'])) {
						foreach ($nodeType['childNodes'] as &$type) {
							$type = str_replace(
								array(
									'TYPO3.Neos.NodeTypes:Page',
									'TYPO3.Neos.NodeTypes:ContentObject',
									'TYPO3.Neos.NodeTypes:Section',
									'TYPO3.Neos.NodeTypes:AbstractNode',
									'TYPO3.Neos.NodeTypes:Plugin',
									'TYPO3.Neos.NodeTypes:Folder'
								),
								array(
									'TYPO3.Neos:Page',
									'TYPO3.Neos:Content',
									'TYPO3.Neos:ContentCollection',
									'TYPO3.Neos:Node',
									'TYPO3.Neos:Plugin',
									'TYPO3.Neos:Document'
								),
								$type
							);
						}
					}
				}
			},
			TRUE
		);
	}

}
