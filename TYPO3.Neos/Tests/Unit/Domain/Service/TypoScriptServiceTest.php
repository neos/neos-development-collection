<?php
namespace TYPO3\TYPO3\Tests\Unit\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the TypoScript Service
 *
 */
class TypoScriptServiceTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function readExternalTypoScriptFilesReturnsTypoScriptFilesSortedInNaturalOrder() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('SitePackage'));
		file_put_contents('vfs://SitePackage/Root.ts2', 'ROOT');
		file_put_contents('vfs://SitePackage/4 Four.ts2', 'FOUR');
		file_put_contents('vfs://SitePackage/Default.ts2', 'DEFAULT');
		file_put_contents('vfs://SitePackage/10 Ten.ts2', 'TEN');

		$expectedTypoScript = 'FOUR' . chr(10) . 'TEN' . chr(10) . 'DEFAULT' . chr(10) . 'ROOT' . chr(10);

		$typoScriptService = $this->getAccessibleMock('TYPO3\TYPO3\Domain\Service\TypoScriptService', array('dummy'), array(), '', FALSE);
		$this->assertEquals($expectedTypoScript, $typoScriptService->_call('readExternalTypoScriptFiles', 'vfs://SitePackage/'));
	}

}

?>