<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Utility\Files;

/**
 * Rename "renderTypoScript" VH to just "render"
 */
class Version20130516235550 extends AbstractMigration {

	/**
	 * @return void
	 */
	public function up() {
		$this->searchAndReplaceRegex(
			'/(:render\\s+path=")([^"]+)"/',
			function (array $matches) {
				return $matches[1] . str_replace('/', '.', $matches[2] . '"');
			},
			array('html')
		);
	}

}

?>