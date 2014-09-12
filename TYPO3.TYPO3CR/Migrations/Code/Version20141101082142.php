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
 * Adjust namespaces for TYPO3CR related FlowQuery operations that were moved from TYPO3.Neos to TYPO3.TYPO3CR
 */
class Version20141101082142 extends AbstractMigration {

	/**
	 * @return void
	 */
	public function up() {
		$this->searchAndReplace('TYPO3\Neos\TypoScript\FlowQueryOperations', 'TYPO3\TYPO3CR\Eel\FlowQueryOperations', array('php', 'yaml'));
	}

}
