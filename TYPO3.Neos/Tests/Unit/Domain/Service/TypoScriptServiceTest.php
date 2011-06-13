<?php
namespace F3\TYPO3\Tests\Unit\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the TypoScript Service
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TypoScriptServiceTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function readExternalTypoScriptFilesReturnsTypoScriptFilesSortedInNaturalOrder() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('SitePackage'));
		file_put_contents('vfs://SitePackage/Root.ts2', 'ROOT');
		file_put_contents('vfs://SitePackage/4 Four.ts2', 'FOUR');
		file_put_contents('vfs://SitePackage/Default.ts2', 'DEFAULT');
		file_put_contents('vfs://SitePackage/10 Ten.ts2', 'TEN');

		$expectedTypoScript = 'FOUR' . chr(10) . 'TEN' . chr(10) . 'DEFAULT' . chr(10) . 'ROOT' . chr(10);

		$typoScriptService = $this->getAccessibleMock('F3\TYPO3\Domain\Service\TypoScriptService', array('dummy'), array(), '', FALSE);
		$this->assertEquals($expectedTypoScript, $typoScriptService->_call('readExternalTypoScriptFiles', 'vfs://SitePackage/'));
	}

}

?>