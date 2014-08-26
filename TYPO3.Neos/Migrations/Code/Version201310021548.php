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

use TYPO3\Flow\Utility\Files;

/**
 * Change TS object names in TypoScript files:
 *
 * ContentCollection.Default -> ContentCollection
 * PrimaryContentCollection -> PrimaryContent
 */
class Version201310021548 extends AbstractMigration {

	/**
	 * @return void
	 */
	public function up() {
		$this->searchAndReplace('ContentCollection.Default', 'ContentCollection', array('ts2'));
		$this->searchAndReplace('PrimaryContentCollection', 'PrimaryContent', array('ts2'));
	}

}
