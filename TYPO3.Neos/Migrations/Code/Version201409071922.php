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
 * Adjust to updated folder name for TypoScript in site packages
 */
class Version201409071922 extends AbstractMigration {

	/**
	 * @return void
	 */
	public function up() {
		$this->moveFile('Resources/Private/TypoScripts/Library/*', 'Resources/Private/TypoScript');
		$this->searchAndReplace('Resources/Private/TypoScripts/Library/', 'Resources/Private/TypoScript/', array('ts2'));
	}

}
